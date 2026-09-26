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
            $this->assertStagingPathIsSafe();

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

            $stagingRoot = $this->projectDir.'/var/execution-staging';
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

            $path = $root.'/'.$filename;
            $stagingPath = $stagingRoot.'/'.$attempt->getId().'.tmp';
            $handle = @fopen($stagingPath, 'x');
            if (false === $handle) {
                $attempt->fail('staging_target_unavailable');
                $this->executions->save($attempt);

                return $attempt;
            }

            $written = 0;
            $writeFailed = false;
            try {
                $length = strlen($content);
                while ($written < $length) {
                    $chunk = fwrite($handle, substr($content, $written));
                    if (false === $chunk || 0 === $chunk) {
                        $writeFailed = true;
                        break;
                    }
                    $written += $chunk;
                }
                fflush($handle);
            } finally {
                fclose($handle);
            }

            if ($writeFailed) {
                @unlink($stagingPath);
                $attempt->fail('staging_write_failed');
                $this->executions->save($attempt);

                return $attempt;
            }

            $stagingHash = @hash_file('sha256', $stagingPath);
            if (false === $stagingHash || $stagingHash !== $attempt->getContentSha256()) {
                @unlink($stagingPath);
                $attempt->fail('staging_verification_failed');
                $this->executions->save($attempt);

                return $attempt;
            }

            if (file_exists($path)) {
                @unlink($stagingPath);
                $attempt->fail('target_exists_or_unavailable');
                $this->executions->save($attempt);

                return $attempt;
            }

            try {
                $root = $this->resolveAuthorizedOutputRoot($scope['root']);
            } catch (\DomainException) {
                @unlink($stagingPath);
                $attempt->fail('execution_root_untrusted');
                $this->executions->save($attempt);

                return $attempt;
            }
            $path = $root.'/'.$filename;

            $attempt->startEffect();
            $this->executions->save($attempt);

            // A hard link publishes the fully written inode atomically and fails
            // rather than overwriting an existing target.
            if (!@link($stagingPath, $path)) {
                @unlink($stagingPath);
                $attempt->fail(file_exists($path) ? 'target_exists_or_unavailable' : 'atomic_publish_unavailable');
                $this->executions->save($attempt);

                return $attempt;
            }
            $actualHash = @hash_file('sha256', $path);
            if (false === $actualHash) {
                // The public effect happened, but evidence is temporarily unreadable.
                // Preserve EFFECT_STARTED for later reconciliation.
                return $attempt;
            }
            if ($actualHash !== $attempt->getContentSha256()) {
                $attempt->fail('verification_failed');
                $this->executions->save($attempt);

                return $attempt;
            }

            $attempt->succeed($written);
            $this->executions->save($attempt);
            @unlink($stagingPath);

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
            if (null === $attempt
                || !in_array($attempt->getStatus(), [ExecutionAttempt::PREPARED, ExecutionAttempt::EFFECT_STARTED], true)) {
                return $attempt;
            }

            $scope = $this->assertScopeAllowsLocalFile($authorization);
            $root = $this->resolveAuthorizedOutputRoot($scope['root']);
            $stagingRoot = $this->resolveStagingRoot();
            $filename = basename($attempt->getTargetPath());
            $path = $root.'/'.$filename;
            $stagingPath = $stagingRoot.'/'.$attempt->getId().'.tmp';
            $exists = is_file($path);

            if (ExecutionAttempt::PREPARED === $attempt->getStatus()) {
                if ($exists) {
                    $attempt->fail('prepared_target_exists_without_start_evidence');
                    $this->executions->save($attempt);
                }

                return $attempt;
            }

            if (!$exists || !is_file($stagingPath)) {
                // Without both links we cannot prove that this attempt published
                // the target. Preserve EFFECT_STARTED rather than guessing.
                return $attempt;
            }

            $targetStat = @stat($path);
            $stagingStat = @stat($stagingPath);
            if (false === $targetStat || false === $stagingStat) {
                return $attempt;
            }
            if (($targetStat['dev'] ?? null) !== ($stagingStat['dev'] ?? null)
                || ($targetStat['ino'] ?? null) !== ($stagingStat['ino'] ?? null)) {
                $attempt->fail('recovery_ownership_mismatch');
                $this->executions->save($attempt);

                return $attempt;
            }

            $actualHash = @hash_file('sha256', $path);
            if (false === $actualHash) {
                return $attempt;
            }
            if ($actualHash === $attempt->getContentSha256()) {
                $size = filesize($path);
                $attempt->succeed(false === $size ? 0 : $size);
                $this->executions->save($attempt);
                @unlink($stagingPath);

                return $attempt;
            }

            $attempt->fail('recovery_hash_mismatch');
            $this->executions->save($attempt);

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


    private function assertStagingPathIsSafe(): void
    {
        $projectRoot = realpath($this->projectDir);
        if (false === $projectRoot || !is_dir($projectRoot)) {
            throw new \DomainException('The project root cannot be resolved safely.');
        }

        $varPath = $this->projectDir.'/var';
        $stagingPath = $varPath.'/execution-staging';
        if (is_link($varPath) || is_link($stagingPath)) {
            throw new \DomainException('The execution staging root cannot pass through a symlink.');
        }

        $varRoot = realpath($varPath);
        if (false === $varRoot || !is_dir($varRoot)
            || $varRoot !== $projectRoot.DIRECTORY_SEPARATOR.'var') {
            throw new \DomainException('The execution staging root cannot be proven inside the project root.');
        }

        if (is_dir($stagingPath)) {
            $this->resolveStagingRoot();
        }
    }

    private function resolveStagingRoot(): string
    {
        $projectRoot = realpath($this->projectDir);
        $varPath = $this->projectDir.'/var';
        $stagingPath = $varPath.'/execution-staging';
        if (false === $projectRoot || is_link($varPath) || is_link($stagingPath)) {
            throw new \DomainException('The execution staging root cannot be resolved safely.');
        }

        $varRoot = realpath($varPath);
        $stagingRoot = realpath($stagingPath);
        if (false === $varRoot || false === $stagingRoot
            || !is_dir($varRoot) || !is_dir($stagingRoot)
            || $varRoot !== $projectRoot.DIRECTORY_SEPARATOR.'var'
            || $stagingRoot !== $varRoot.DIRECTORY_SEPARATOR.'execution-staging'
            || !$this->isWithinRoot($stagingRoot, $projectRoot)) {
            throw new \DomainException('The execution staging root cannot be proven inside the project root.');
        }

        $publicRoot = realpath($this->projectDir.'/public');
        if (false !== $publicRoot && $this->isWithinRoot($stagingRoot, $publicRoot)) {
            throw new \DomainException('The execution staging root must remain outside the public document root.');
        }

        return $stagingRoot;
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
        if (false === $publicRoot || !is_dir($publicRoot) || !$this->isWithinRoot($publicRoot, $projectRoot)) {
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
            || !$this->isWithinRoot($publicRoot, $projectRoot)
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
