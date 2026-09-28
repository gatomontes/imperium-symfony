<?php

namespace App\Curia;

use App\Atheneum\AuthorizationRecords;
use App\Atheneum\ExecutionRecords;
use App\Atheneum\InterviewRecords;
use App\Atheneum\ProposalRecords;
use App\Entity\Authorization;
use App\Entity\ExecutionAttempt;
use App\Entity\Proposal;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Lock\LockFactory;

class LocalFileExecutionService
{
    private const MAX_CONTENT_BYTES = 32768;

    public function __construct(
        private InterviewRecords $interviews,
        private ProposalRecords $proposals,
        private AuthorizationRecords $authorizations,
        private ExecutionRecords $executions,
        private LockFactory $lockFactory,
        #[Autowire(param: 'kernel.project_dir')]
        private string $projectDir,
        #[Autowire('%env(default::IMPERIUM_EXECUTION_STAGING_DIR)%')]
        private ?string $configuredStagingRoot = null,
    ) {
    }

    public function execute(string $interviewId, string $filename, string $content): ExecutionAttempt
    {
        $lock = $this->lockFactory->createLock('imperium.interview.'.$interviewId);
        if (!$lock->acquire()) {
            throw new \DomainException('This interview is being updated by another process. Try again after it finishes.');
        }

        try {
            [$authorization] = $this->authorizedContext($interviewId);
            $existing = $this->executions->forAuthorization($authorization);
            if (null !== $existing) {
                throw new \DomainException('This authorization already has an execution attempt. Reopen the mission to review its result.');
            }

            $scope = $this->assertScopeAllowsLocalFile($authorization);
            $filename = $this->validateFilename($filename);
            if (strlen($content) > $scope['maxBytes']) {
                throw new \DomainException('Local file content exceeds the authorized maxBytes limit.');
            }

            $this->assertAuthorizedOutputPathIsSafe($scope['root']);
            if (is_link($this->projectDir.'/var')
                || is_link($this->projectDir.'/var/execution-staging')
                || (null !== $this->configuredStagingRoot && is_link($this->configuredStagingRoot))) {
                throw new \DomainException('The execution staging root cannot pass through a symlink.');
            }

            $relativePath = $scope['root'].'/'.$filename;
            $attempt = new ExecutionAttempt($authorization, $relativePath, hash('sha256', $content));
            $this->executions->save($attempt);

            $root = $this->projectDir.'/'.$scope['root'];
            if (!is_dir($root) && !mkdir($root, 0775, true) && !is_dir($root)) {
                $attempt->fail('execution_directory_unavailable');
                $this->executions->save($attempt);

                return $attempt;
            }

            try {
                $root = $this->resolveAuthorizedOutputRoot($scope['root']);
            } catch (\DomainException) {
                $attempt->fail('execution_root_untrusted');
                $this->executions->save($attempt);

                return $attempt;
            }

            try {
                $stagingRoot = $this->stagingPathFor($root);
                $this->assertStagingPathIsSafe($stagingRoot, $root);
            } catch (\DomainException) {
                $attempt->fail('staging_root_untrusted');
                $this->executions->save($attempt);

                return $attempt;
            }
            if (!is_dir($stagingRoot) && !mkdir($stagingRoot, 0775, true) && !is_dir($stagingRoot)) {
                $attempt->fail('execution_directory_unavailable');
                $this->executions->save($attempt);

                return $attempt;
            }

            try {
                $stagingRoot = $this->resolveStagingRoot();
            } catch (\DomainException) {
                $attempt->fail('staging_root_untrusted');
                $this->executions->save($attempt);

                return $attempt;
            }

            $stagingName = $attempt->getId().'.tmp';
            $stagingPath = $stagingRoot.'/'.$stagingName;
            $publishWitnessName = '.imperium-'.$attempt->getId().'.publish';
            try {
                $stagingResult = $this->inAnchoredDirectory($stagingRoot, function () use ($stagingName, $content, $attempt): array {
                    $handle = @fopen($stagingName, 'x+');
                    if (false === $handle) {
                        return ['failure' => 'staging_target_unavailable', 'written' => 0, 'handle' => null, 'stat' => null];
                    }

                    $written = 0;
                    $length = strlen($content);
                    while ($written < $length) {
                        $chunk = fwrite($handle, substr($content, $written));
                        if (false === $chunk || 0 === $chunk) {
                            fclose($handle);
                            @unlink($stagingName);

                            return ['failure' => 'staging_write_failed', 'written' => $written, 'handle' => null, 'stat' => null];
                        }
                        $written += $chunk;
                    }

                    if (!fflush($handle)) {
                        fclose($handle);
                        @unlink($stagingName);

                        return ['failure' => 'staging_write_failed', 'written' => $written, 'handle' => null, 'stat' => null];
                    }

                    $stagingStat = @fstat($handle);
                    if (false === $stagingStat || 0 !== fseek($handle, 0)) {
                        fclose($handle);
                        @unlink($stagingName);

                        return ['failure' => 'staging_verification_failed', 'written' => $written, 'handle' => null, 'stat' => null];
                    }

                    $hash = hash_init('sha256');
                    if (false === hash_update_stream($hash, $handle)) {
                        fclose($handle);
                        @unlink($stagingName);

                        return ['failure' => 'staging_verification_failed', 'written' => $written, 'handle' => null, 'stat' => null];
                    }
                    $stagingHash = hash_final($hash);
                    if ($stagingHash !== $attempt->getContentSha256()) {
                        fclose($handle);
                        @unlink($stagingName);

                        return ['failure' => 'staging_verification_failed', 'written' => $written, 'handle' => null, 'stat' => null];
                    }

                    return [
                        'failure' => null,
                        'written' => $written,
                        'handle' => $handle,
                        'stat' => $stagingStat,
                    ];
                });
            } catch (\DomainException) {
                $attempt->fail('staging_root_untrusted');
                $this->executions->save($attempt);

                return $attempt;
            }

            $written = $stagingResult['written'];
            if (null !== $stagingResult['failure']) {
                $attempt->fail($stagingResult['failure']);
                $this->executions->save($attempt);

                return $attempt;
            }

            $stagingHandle = $stagingResult['handle'];
            $stagingIdentity = $stagingResult['stat'];

            try {
                $targetExists = $this->inAnchoredDirectory($root, static fn (): bool => file_exists($filename));
            } catch (\DomainException) {
                fclose($stagingHandle);
                $this->removeStagingFile($stagingRoot, $stagingName, $stagingIdentity);
                $attempt->fail('execution_root_untrusted');
                $this->executions->save($attempt);

                return $attempt;
            }

            if ($targetExists) {
                fclose($stagingHandle);
                $this->removeStagingFile($stagingRoot, $stagingName, $stagingIdentity);
                $attempt->fail('target_exists_or_unavailable');
                $this->executions->save($attempt);

                return $attempt;
            }

            try {
                $sourceStillMatches = $this->inAnchoredDirectory($stagingRoot, static function () use ($stagingName, $stagingIdentity): bool {
                    $current = @stat($stagingName);

                    return false !== $current
                        && ($current['dev'] ?? null) === ($stagingIdentity['dev'] ?? null)
                        && ($current['ino'] ?? null) === ($stagingIdentity['ino'] ?? null);
                });
                $root = $this->resolveAuthorizedOutputRoot($scope['root']);
            } catch (\DomainException) {
                fclose($stagingHandle);
                $this->removeStagingFile($stagingRoot, $stagingName, $stagingIdentity);
                $attempt->fail('execution_root_untrusted');
                $this->executions->save($attempt);

                return $attempt;
            }

            if (!$sourceStillMatches) {
                fclose($stagingHandle);
                $this->removeStagingFile($stagingRoot, $stagingName, $stagingIdentity);
                $attempt->fail('staging_identity_changed_before_publish');
                $this->executions->save($attempt);

                return $attempt;
            }

            $attempt->startEffect();
            $this->executions->save($attempt);

            try {
                $publishResult = $this->inAnchoredDirectory($root, function () use ($stagingPath, $publishWitnessName, $filename, $attempt, $stagingIdentity): array {
                    $removeOwnedWitness = static function () use ($publishWitnessName, $stagingIdentity): bool {
                        $lstat = @lstat($publishWitnessName);
                        $stat = @stat($publishWitnessName);
                        if (false === $lstat || false === $stat
                            || (($lstat['mode'] ?? 0) & 0170000) !== 0100000
                            || ($stat['dev'] ?? null) !== ($stagingIdentity['dev'] ?? null)
                            || ($stat['ino'] ?? null) !== ($stagingIdentity['ino'] ?? null)) {
                            return false;
                        }

                        return @unlink($publishWitnessName);
                    };

                    // The staging pathname can be replaced after verification. Create
                    // a private-named witness in the already anchored output directory,
                    // then prove that witness is the exact still-open staging inode.
                    // Final publication links witness -> target entirely inside this
                    // anchored directory, so neither side can be redirected by a
                    // pathname swap between verification and publication.
                    // Never remove a preexisting predictable witness name: it
                    // may belong to another actor. Exclusive link creation is the
                    // collision detector.
                    if (!@link($stagingPath, $publishWitnessName)) {
                        return [
                            'published' => false,
                            'failure' => 'publish_witness_exists_or_unavailable',
                            'hash' => false,
                        ];
                    }

                    $witnessLstat = @lstat($publishWitnessName);
                    $witnessStat = @stat($publishWitnessName);
                    if (false === $witnessLstat || false === $witnessStat
                        || (($witnessLstat['mode'] ?? 0) & 0170000) !== 0100000
                        || ($witnessStat['dev'] ?? null) !== ($stagingIdentity['dev'] ?? null)
                        || ($witnessStat['ino'] ?? null) !== ($stagingIdentity['ino'] ?? null)) {
                        $removeOwnedWitness();

                        return [
                            'published' => false,
                            'failure' => 'staging_identity_changed_before_publish',
                            'hash' => false,
                        ];
                    }
                    if (!@link($publishWitnessName, $filename)) {
                        $removeOwnedWitness();
                        return [
                            'published' => false,
                            'failure' => file_exists($filename) || is_link($filename) ? 'target_exists_or_unavailable' : 'atomic_publish_unavailable',
                            'hash' => false,
                        ];
                    }

                    $targetHandle = @fopen($filename, 'rb');
                    if (false === $targetHandle) {
                        // Publication may have happened, but the target cannot be
                        // verified coherently. Retain EFFECT_STARTED plus witness
                        // and staging identity for recovery.
                        return [
                            'published' => true,
                            'failure' => null,
                            'hash' => false,
                        ];
                    }

                    try {
                        $targetStat = @fstat($targetHandle);
                        if (false === $targetStat
                            || (($targetStat['mode'] ?? 0) & 0170000) !== 0100000
                            || ($targetStat['dev'] ?? null) !== ($stagingIdentity['dev'] ?? null)
                            || ($targetStat['ino'] ?? null) !== ($stagingIdentity['ino'] ?? null)) {
                            $removeOwnedWitness();

                            return [
                                'published' => false,
                                'failure' => 'staging_identity_changed_before_publish',
                                'hash' => false,
                            ];
                        }

                        $hash = hash_init('sha256');
                        if (false === hash_update_stream($hash, $targetHandle)) {
                            return [
                                'published' => true,
                                'failure' => null,
                                'hash' => false,
                            ];
                        }
                        $actualHash = hash_final($hash);

                        // Ensure the pathname still names this exact opened regular
                        // inode. A replacement or symlink must not inherit success.
                        $currentLstat = @lstat($filename);
                        $pathStillOwned = false !== $currentLstat
                            && (($currentLstat['mode'] ?? 0) & 0170000) === 0100000
                            && ($currentLstat['dev'] ?? null) === ($targetStat['dev'] ?? null)
                            && ($currentLstat['ino'] ?? null) === ($targetStat['ino'] ?? null);
                        if (!$pathStillOwned) {
                            $removeOwnedWitness();

                            return [
                                'published' => false,
                                'failure' => 'staging_identity_changed_before_publish',
                                'hash' => false,
                            ];
                        }

                        if ($actualHash !== $attempt->getContentSha256()) {
                            // Recheck ownership immediately before any unlink. If
                            // identity is lost, leave the replacement untouched.
                            $removeLstat = @lstat($filename);
                            $stillOwned = false !== $removeLstat
                                && (($removeLstat['mode'] ?? 0) & 0170000) === 0100000
                                && ($removeLstat['dev'] ?? null) === ($targetStat['dev'] ?? null)
                                && ($removeLstat['ino'] ?? null) === ($targetStat['ino'] ?? null);
                            if (!$stillOwned) {
                                $removeOwnedWitness();

                                return [
                                    'published' => false,
                                    'failure' => 'staging_identity_changed_before_publish',
                                    'hash' => false,
                                ];
                            }

                            if (!@unlink($filename)) {
                                // The attempt-owned public link could not be removed.
                                // Preserve EFFECT_STARTED so recovery can retry.
                                return [
                                    'published' => true,
                                    'failure' => null,
                                    'hash' => false,
                                ];
                            }

                            $removeOwnedWitness();

                            return [
                                'published' => false,
                                'failure' => 'verification_failed',
                                'hash' => $actualHash,
                            ];
                        }

                        if (!$removeOwnedWitness()) {
                            return [
                                'published' => true,
                                'failure' => null,
                                'hash' => false,
                            ];
                        }

                        return [
                            'published' => true,
                            'failure' => null,
                            'hash' => $actualHash,
                        ];
                    } finally {
                        fclose($targetHandle);
                    }
                });
            } catch (\DomainException) {
                fclose($stagingHandle);
                // Publication may have occurred before the directory moved.
                // Retain effect-start evidence and its staging inode for review.
                return $attempt;
            }

            fclose($stagingHandle);

            try {
                $witnessRetained = $this->inAnchoredDirectory(
                    $root,
                    static fn (): bool => file_exists($publishWitnessName) || is_link($publishWitnessName),
                );
            } catch (\DomainException) {
                $witnessRetained = true;
            }

            if (null !== $publishResult['failure']) {
                if (!$witnessRetained) {
                    $this->removeStagingFile($stagingRoot, $stagingName, $stagingIdentity);
                }
                $attempt->fail($publishResult['failure']);
                $this->executions->save($attempt);

                return $attempt;
            }

            $actualHash = $publishResult['hash'];
            if (false === $actualHash) {
                // The public effect happened, but evidence is temporarily unreadable
                // or cleanup of an attempt-owned mismatched target could not be proven.
                // Preserve EFFECT_STARTED for later reconciliation.
                return $attempt;
            }

            $attempt->succeed($written);
            $this->executions->save($attempt);
            if (!$witnessRetained) {
                $this->removeStagingFile($stagingRoot, $stagingName, $stagingIdentity);
            }

            return $attempt;
        } finally {
            $lock->release();
        }
    }

    public function reconcile(string $interviewId): ?ExecutionAttempt
    {
        $lock = $this->lockFactory->createLock('imperium.interview.'.$interviewId);
        if (!$lock->acquire()) {
            throw new \DomainException('This interview is being updated by another process. Try again after it finishes.');
        }

        try {
            [$authorization] = $this->authorizedContext($interviewId);
            $attempt = $this->executions->forAuthorization($authorization);
            if (null === $attempt) {
                return null;
            }

            $scope = $this->assertScopeAllowsLocalFile($authorization);
            $filename = basename($attempt->getTargetPath());
            $stagingName = $attempt->getId().'.tmp';
            $publishWitnessName = '.imperium-'.$attempt->getId().'.publish';

            if (in_array($attempt->getStatus(), [ExecutionAttempt::SUCCEEDED, ExecutionAttempt::FAILED], true)) {
                try {
                    $stagingPath = $this->stagingPathFor($this->resolveAuthorizedOutputRoot($scope['root']));
                    if (is_dir($stagingPath) && !is_link($stagingPath)) {
                        $stagingRoot = $this->resolveStagingRoot();
                        $stagingEvidence = $this->stagingFileEvidence($stagingRoot, $stagingName);
                        if (false === $stagingEvidence['exists']) {
                            return $attempt;
                        }
                        $stagingStat = $stagingEvidence['stat'];
                        if (!is_array($stagingStat)) {
                            return $attempt;
                        }

                        $root = $this->resolveAuthorizedOutputRoot($scope['root']);
                        if ($this->removePublishWitnessIfOwned($root, $publishWitnessName, $stagingStat)) {
                            $this->removeStagingFile($stagingRoot, $stagingName, $stagingStat);
                        }
                    }
                } catch (\DomainException) {
                    // Terminal evidence remains authoritative; cleanup can be retried later.
                }

                return $attempt;
            }

            if (!in_array($attempt->getStatus(), [ExecutionAttempt::PREPARED, ExecutionAttempt::EFFECT_STARTED], true)) {
                return $attempt;
            }

            if (ExecutionAttempt::PREPARED === $attempt->getStatus()) {
                try {
                    $stagingPath = $this->stagingPathFor($this->resolveAuthorizedOutputRoot($scope['root']));
                    if (is_dir($stagingPath) && !is_link($stagingPath)) {
                        $stagingRoot = $this->resolveStagingRoot();
                        $this->removeStagingFile($stagingRoot, $stagingName);
                    }
                } catch (\DomainException) {
                    // PREPARED has no effect-start evidence; cleanup is best-effort.
                }

                $outputPath = $this->projectDir.'/'.$scope['root'];
                if (!is_dir($outputPath)) {
                    if (is_link($outputPath)) {
                        throw new \DomainException('The authorized output root cannot be a symlink.');
                    }

                    return $attempt;
                }

                $root = $this->resolveAuthorizedOutputRoot($scope['root']);
                $exists = $this->inAnchoredDirectory($root, static fn (): bool => false !== @lstat($filename));
                if ($exists) {
                    $attempt->fail('prepared_target_exists_without_start_evidence');
                    $this->executions->save($attempt);
                }

                return $attempt;
            }

            $root = $this->resolveAuthorizedOutputRoot($scope['root']);
            $stagingRoot = $this->resolveStagingRoot();

            $targetEvidence = $this->inAnchoredDirectory($root, static function () use ($filename): ?array {
                $initialLstat = @lstat($filename);
                if (false === $initialLstat) {
                    return null;
                }

                if ((($initialLstat['mode'] ?? 0) & 0170000) === 0120000) {
                    return ['symlink' => true, 'stat' => false, 'hash' => false];
                }
                if ((($initialLstat['mode'] ?? 0) & 0170000) !== 0100000) {
                    return ['symlink' => false, 'nonregular' => true, 'stat' => false, 'hash' => false];
                }

                $handle = @fopen($filename, 'rb');
                if (false === $handle) {
                    return ['symlink' => false, 'stat' => false, 'hash' => false];
                }

                try {
                    $stat = @fstat($handle);
                    if (false === $stat) {
                        return ['symlink' => false, 'stat' => false, 'hash' => false];
                    }

                    $hash = hash_init('sha256');
                    if (false === hash_update_stream($hash, $handle)) {
                        return ['symlink' => false, 'stat' => false, 'hash' => false];
                    }
                    $actualHash = hash_final($hash);

                    $finalLstat = @lstat($filename);
                    if (false === $finalLstat) {
                        return null;
                    }
                    if ((($finalLstat['mode'] ?? 0) & 0170000) === 0120000) {
                        return ['symlink' => true, 'stat' => false, 'hash' => false];
                    }
                    if ((($finalLstat['mode'] ?? 0) & 0170000) !== 0100000
                        || ($finalLstat['dev'] ?? null) !== ($stat['dev'] ?? null)
                        || ($finalLstat['ino'] ?? null) !== ($stat['ino'] ?? null)) {
                        return ['symlink' => false, 'stat' => false, 'hash' => false];
                    }

                    return ['symlink' => false, 'stat' => $stat, 'hash' => $actualHash];
                } finally {
                    fclose($handle);
                }
            });
            $stagingEvidence = $this->inAnchoredDirectory($stagingRoot, static function () use ($stagingName): ?array {
                $initialLstat = @lstat($stagingName);
                if (false === $initialLstat) {
                    return null;
                }

                if ((($initialLstat['mode'] ?? 0) & 0170000) === 0120000) {
                    return ['symlink' => true, 'stat' => false];
                }
                if ((($initialLstat['mode'] ?? 0) & 0170000) !== 0100000) {
                    return ['symlink' => false, 'stat' => false];
                }

                $handle = @fopen($stagingName, 'rb');
                if (false === $handle) {
                    return ['symlink' => false, 'stat' => false];
                }

                try {
                    $stat = @fstat($handle);
                    $finalLstat = @lstat($stagingName);
                    if (false === $stat || false === $finalLstat
                        || (($finalLstat['mode'] ?? 0) & 0170000) !== 0100000
                        || ($initialLstat['dev'] ?? null) !== ($stat['dev'] ?? null)
                        || ($initialLstat['ino'] ?? null) !== ($stat['ino'] ?? null)
                        || ($finalLstat['dev'] ?? null) !== ($stat['dev'] ?? null)
                        || ($finalLstat['ino'] ?? null) !== ($stat['ino'] ?? null)) {
                        return ['symlink' => false, 'stat' => false];
                    }

                    return ['symlink' => false, 'stat' => $stat];
                } finally {
                    fclose($handle);
                }
            });

            if (true === ($stagingEvidence['symlink'] ?? false)) {
                return $attempt;
            }

            $stagingStat = $stagingEvidence['stat'] ?? null;
            if (is_array($stagingStat)
                && !$this->removePublishWitnessIfOwned($root, $publishWitnessName, $stagingStat)) {
                // A retained witness still depends on this staging inode for safe
                // cleanup. Keep EFFECT_STARTED and preserve staging until witness
                // ownership/removal can be proven on a later reopen.
                return $attempt;
            }

            if (true === ($targetEvidence['symlink'] ?? false)) {
                $attempt->fail('recovery_target_symlink');
                $this->executions->save($attempt);
                if (is_array($stagingStat)) {
                    $this->removeStagingFile($stagingRoot, $stagingName, $stagingStat);
                }

                return $attempt;
            }
            if (true === ($targetEvidence['nonregular'] ?? false)) {
                $attempt->fail('recovery_ownership_mismatch');
                $this->executions->save($attempt);
                if (is_array($stagingStat)) {
                    $this->removeStagingFile($stagingRoot, $stagingName, $stagingStat);
                }

                return $attempt;
            }
            if (null === $targetEvidence || null === $stagingEvidence
                || false === $targetEvidence['stat'] || false === $stagingStat) {
                // Without both regular-file links we cannot prove that this attempt
                // published the target. Preserve EFFECT_STARTED rather than guessing.
                return $attempt;
            }

            $targetStat = $targetEvidence['stat'];
            if (($targetStat['dev'] ?? null) !== ($stagingStat['dev'] ?? null)
                || ($targetStat['ino'] ?? null) !== ($stagingStat['ino'] ?? null)) {
                $attempt->fail('recovery_ownership_mismatch');
                $this->executions->save($attempt);
                $this->removeStagingFile($stagingRoot, $stagingName, $stagingStat);

                return $attempt;
            }

            $actualHash = $targetEvidence['hash'];
            if (false === $actualHash) {
                return $attempt;
            }
            if ($actualHash === $attempt->getContentSha256()) {
                $size = $targetStat['size'] ?? null;
                if (!is_int($size) || $size < 0) {
                    return $attempt;
                }

                $attempt->succeed($size);
                $this->executions->save($attempt);
                $this->removeStagingFile($stagingRoot, $stagingName, $stagingStat);

                return $attempt;
            }

            $removed = $this->inAnchoredDirectory($root, static function () use ($filename, $targetStat): bool {
                $lstat = @lstat($filename);
                $current = @stat($filename);
                if (false === $lstat || false === $current
                    || (($lstat['mode'] ?? 0) & 0170000) !== 0100000
                    || ($current['dev'] ?? null) !== ($targetStat['dev'] ?? null)
                    || ($current['ino'] ?? null) !== ($targetStat['ino'] ?? null)) {
                    return false;
                }

                return @unlink($filename);
            });
            if (!$removed) {
                return $attempt;
            }

            $attempt->fail('recovery_hash_mismatch');
            $this->executions->save($attempt);
            $this->removeStagingFile($stagingRoot, $stagingName, $stagingStat);

            return $attempt;
        } finally {
            $lock->release();
        }
    }

    /** @return array{Authorization, Proposal} */
    private function authorizedContext(string $interviewId): array
    {
        $interview = $this->interviews->get($interviewId);
        $proposal = $this->proposals->latest($interview);
        if (null === $proposal || Proposal::APPROVED !== $proposal->getStatus()) {
            throw new \DomainException('An approved proposal is required before execution.');
        }

        $authorization = $this->authorizations->forProposal($proposal);
        if (null === $authorization || Authorization::AUTHORIZED !== $authorization->getStatus()) {
            throw new \DomainException('An authorized resource/effect scope is required before execution.');
        }

        return [$authorization, $proposal];
    }

    /** @return array{capability:string,effect:string,root:string,visibility:string,allowedExtensions:list<string>,maxFiles:int,overwrite:bool,maxBytes:int} */
    private function assertScopeAllowsLocalFile(Authorization $authorization): array
    {
        $scope = $authorization->getExecutionScope();
        if (null === $scope) {
            throw new \DomainException('This authorization has no structured executable scope. Create a new authorization request.');
        }

        $expected = [
            'capability' => 'filesystem.write.public_output',
            'effect' => 'file.create.public',
            'root' => 'public/output',
            'visibility' => 'public',
            'allowedExtensions' => ['txt'],
            'maxFiles' => 1,
            'overwrite' => false,
            'maxBytes' => self::MAX_CONTENT_BYTES,
        ];

        if ($scope !== $expected) {
            throw new \DomainException('The structured authorization scope does not match the supported local-file executor.');
        }

        return $scope;
    }


    /** @return array{exists:bool,stat:array<string, int>|false} */
    private function stagingFileEvidence(string $stagingRoot, string $stagingName): array
    {
        return $this->inAnchoredDirectory($stagingRoot, static function () use ($stagingName): array {
            $lstat = @lstat($stagingName);
            if (false === $lstat) {
                return ['exists' => false, 'stat' => false];
            }
            if ((($lstat['mode'] ?? 0) & 0170000) !== 0100000) {
                return ['exists' => true, 'stat' => false];
            }

            $stat = @stat($stagingName);
            if (false === $stat
                || ($stat['dev'] ?? null) !== ($lstat['dev'] ?? null)
                || ($stat['ino'] ?? null) !== ($lstat['ino'] ?? null)) {
                return ['exists' => true, 'stat' => false];
            }

            return ['exists' => true, 'stat' => $stat];
        });
    }

    private function removePublishWitnessIfOwned(string $root, string $witnessName, array $ownedStat): bool
    {
        try {
            return $this->inAnchoredDirectory($root, static function () use ($witnessName, $ownedStat): bool {
                // Successful directory enumeration is positive evidence that the
                // witness name is absent. An lstat() failure alone is inconclusive.
                $entries = @scandir('.');
                if (false === $entries) {
                    return false;
                }
                if (!in_array($witnessName, $entries, true)) {
                    return true;
                }

                $lstat = @lstat($witnessName);
                $stat = @stat($witnessName);
                if (false === $lstat || false === $stat
                    || (($lstat['mode'] ?? 0) & 0170000) !== 0100000
                    || ($stat['dev'] ?? null) !== ($ownedStat['dev'] ?? null)
                    || ($stat['ino'] ?? null) !== ($ownedStat['ino'] ?? null)) {
                    return false;
                }

                if (!@unlink($witnessName)) {
                    return false;
                }

                $after = @scandir('.');

                return false !== $after && !in_array($witnessName, $after, true);
            });
        } catch (\DomainException) {
            return false;
        }
    }

    private function removeStagingFile(string $stagingRoot, string $stagingName, ?array $ownedStat = null): void
    {
        try {
            $this->inAnchoredDirectory($stagingRoot, static function () use ($stagingName, $ownedStat): void {
                clearstatcache(true, $stagingName);
                $current = @lstat($stagingName);
                if (false === $current || (($current['mode'] ?? 0) & 0170000) !== 0100000) {
                    return;
                }
                if (null !== $ownedStat && (($current['dev'] ?? null) !== ($ownedStat['dev'] ?? null)
                    || ($current['ino'] ?? null) !== ($ownedStat['ino'] ?? null))) {
                    return;
                }

                @unlink($stagingName);
            });
        } catch (\DomainException) {
            // Cleanup is best-effort; retained evidence remains authoritative.
        }
    }

    private function inAnchoredDirectory(string $expectedDirectory, callable $operation): mixed
    {
        $previousDirectory = getcwd();
        if (false === $previousDirectory || !@chdir($expectedDirectory)) {
            throw new \DomainException('The execution directory could not be anchored safely.');
        }

        try {
            $currentDirectory = realpath('.');
            if (false === $currentDirectory || $currentDirectory !== $expectedDirectory) {
                throw new \DomainException('The execution directory changed before the filesystem operation.');
            }

            $result = $operation();
            clearstatcache(true);
            $currentDirectory = realpath('.');
            $namedDirectory = realpath($expectedDirectory);
            if (false === $currentDirectory || $currentDirectory !== $expectedDirectory
                || $namedDirectory !== $expectedDirectory) {
                throw new \DomainException('The execution directory moved during the filesystem operation.');
            }

            return $result;
        } finally {
            if (!@chdir($previousDirectory)) {
                throw new \RuntimeException('Could not restore the process working directory after execution.');
            }
        }
    }

    private function stagingPathFor(string $outputRoot): string
    {
        $projectRoot = realpath($this->projectDir);
        $outputStat = @stat($outputRoot);
        if (false === $projectRoot || false === $outputStat) {
            throw new \DomainException('The execution staging filesystem cannot be determined.');
        }

        if (null !== $this->configuredStagingRoot && '' !== $this->configuredStagingRoot) {
            return rtrim($this->configuredStagingRoot, '/\\');
        }

        $varRoot = realpath($projectRoot.'/var');
        $varStat = false === $varRoot ? false : @stat($varRoot);
        if (false !== $varStat && $varStat['dev'] === $outputStat['dev']) {
            return $projectRoot.'/var/execution-staging';
        }

        $projectStat = @stat($projectRoot);
        if (false !== $projectStat && $projectStat['dev'] === $outputStat['dev']) {
            return $projectRoot.'/.imperium-execution-staging';
        }

        throw new \DomainException('A private staging directory on the output filesystem is required.');
    }

    private function assertStagingPathIsSafe(string $stagingPath, string $outputRoot): void
    {
        $parent = dirname($stagingPath);
        $resolvedParent = realpath($parent);
        $publicRoot = realpath($this->projectDir.'/public');
        $outputStat = @stat($outputRoot);
        $parentStat = false === $resolvedParent ? false : @stat($resolvedParent);
        if (false === $resolvedParent || false === $publicRoot || false === $outputStat
            || false === $parentStat || !$this->samePath($resolvedParent, $parent)
            || is_link($stagingPath)
            || $this->isWithinRoot($resolvedParent, $publicRoot)
            || $parentStat['dev'] !== $outputStat['dev']) {
            throw new \DomainException('The execution staging root must be private and on the output filesystem.');
        }

        if (is_dir($stagingPath)) {
            $this->resolveStagingRoot();
        }
    }

    private function resolveStagingRoot(): string
    {
        $outputRoot = $this->resolveAuthorizedOutputRoot('public/output');
        $stagingPath = $this->stagingPathFor($outputRoot);
        $resolved = realpath($stagingPath);
        $publicRoot = realpath($this->projectDir.'/public');
        $outputStat = @stat($outputRoot);
        $stagingStat = false === $resolved ? false : @stat($resolved);
        if (false === $resolved || false === $publicRoot || false === $outputStat
            || false === $stagingStat || !is_dir($resolved) || is_link($stagingPath)
            || !$this->samePath($resolved, $stagingPath)
            || $this->isWithinRoot($resolved, $publicRoot)
            || $stagingStat['dev'] !== $outputStat['dev']) {
            throw new \DomainException('The execution staging root cannot be resolved safely.');
        }

        return $resolved;
    }

    private function assertAuthorizedOutputPathIsSafe(string $authorizedRoot): void
    {
        if ('public/output' !== $authorizedRoot) {
            throw new \DomainException('The authorized output root is not supported by this executor.');
        }

        $projectRoot = realpath($this->projectDir);
        if (false === $projectRoot || !is_dir($projectRoot)) {
            throw new \DomainException('The project root cannot be resolved safely.');
        }

        $publicPath = $this->projectDir.'/public';
        if (is_link($publicPath)) {
            throw new \DomainException('The authorized output root cannot pass through a symlink.');
        }

        $publicRoot = realpath($publicPath);
        if (false === $publicRoot || !is_dir($publicRoot)
            || $publicRoot !== $projectRoot.DIRECTORY_SEPARATOR.'public') {
            throw new \DomainException('The authorized output root cannot be proven inside the project root.');
        }

        $outputPath = $this->projectDir.'/'.$authorizedRoot;
        if (is_link($outputPath)) {
            throw new \DomainException('The authorized output root cannot be a symlink.');
        }

        if (is_dir($outputPath)) {
            $this->resolveAuthorizedOutputRoot($authorizedRoot);
        }
    }

    private function resolveAuthorizedOutputRoot(string $authorizedRoot): string
    {
        $this->assertAuthorizedOutputPathShape($authorizedRoot);

        $projectRoot = realpath($this->projectDir);
        $publicPath = $this->projectDir.'/public';
        $outputPath = $this->projectDir.'/'.$authorizedRoot;
        if (false === $projectRoot || is_link($publicPath) || is_link($outputPath)) {
            throw new \DomainException('The authorized output root cannot be resolved safely.');
        }

        $publicRoot = realpath($publicPath);
        $resolvedOutput = realpath($outputPath);
        if (false === $publicRoot || false === $resolvedOutput
            || !is_dir($publicRoot) || !is_dir($resolvedOutput)
            || $publicRoot !== $projectRoot.DIRECTORY_SEPARATOR.'public'
            || !$this->isWithinRoot($resolvedOutput, $projectRoot)
            || $resolvedOutput !== $publicRoot.DIRECTORY_SEPARATOR.'output') {
            throw new \DomainException('The authorized output root cannot be proven inside the project root.');
        }

        return $resolvedOutput;
    }

    private function assertAuthorizedOutputPathShape(string $authorizedRoot): void
    {
        if ('public/output' !== $authorizedRoot) {
            throw new \DomainException('The authorized output root is not supported by this executor.');
        }
    }

    private function isWithinRoot(string $path, string $root): bool
    {
        return $path === $root || str_starts_with($path, $root.DIRECTORY_SEPARATOR);
    }

    private function samePath(string $first, string $second): bool
    {
        $first = str_replace('\\', '/', $first);
        $second = str_replace('\\', '/', $second);

        return DIRECTORY_SEPARATOR === '\\' ? strcasecmp($first, $second) === 0 : $first === $second;
    }

    private function validateFilename(string $filename): string
    {
        $filename = trim($filename);
        if (!preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._-]{0,119}\z/', $filename)
            || in_array($filename, ['.', '..'], true)) {
            throw new \DomainException('Filename must be 1-120 safe characters with no path separators.');
        }

        if ('txt' !== strtolower((string) pathinfo($filename, PATHINFO_EXTENSION))) {
            throw new \DomainException('This executor permits only .txt files in public/output.');
        }

        return $filename;
    }
}
