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

            $this->assertScopeAllowsLocalFile($authorization);
            $filename = $this->validateFilename($filename);
            if (strlen($content) > self::MAX_CONTENT_BYTES) {
                throw new \DomainException('Local file content must be 32768 bytes or fewer.');
            }

            $relativePath = 'var/execution/'.$filename;
            $attempt = new ExecutionAttempt($authorization, $relativePath, hash('sha256', $content));
            $this->executions->save($attempt);

            $root = $this->projectDir.'/var/execution';
            if (!is_dir($root) && !mkdir($root, 0775, true) && !is_dir($root)) {
                $attempt->fail('execution_directory_unavailable');
                $this->executions->save($attempt);

                return $attempt;
            }

            $path = $root.'/'.$filename;
            $handle = @fopen($path, 'x');
            if (false === $handle) {
                $attempt->fail('target_exists_or_unavailable');
                $this->executions->save($attempt);

                return $attempt;
            }

            $written = 0;
            try {
                $attempt->startEffect();
                $this->executions->save($attempt);

                $length = strlen($content);
                while ($written < $length) {
                    $chunk = fwrite($handle, substr($content, $written));
                    if (false === $chunk || 0 === $chunk) {
                        $attempt->fail('write_failed');
                        $this->executions->save($attempt);

                        return $attempt;
                    }
                    $written += $chunk;
                }
                fflush($handle);
            } finally {
                fclose($handle);
            }

            $actualHash = @hash_file('sha256', $path);
            if ($actualHash !== $attempt->getContentSha256()) {
                $attempt->fail('verification_failed');
                $this->executions->save($attempt);

                return $attempt;
            }

            $attempt->succeed($written);
            $this->executions->save($attempt);

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

            $path = $this->projectDir.'/'.$attempt->getTargetPath();
            $exists = is_file($path);

            if (ExecutionAttempt::PREPARED === $attempt->getStatus()) {
                if ($exists) {
                    $attempt->fail('prepared_target_exists_without_start_evidence');
                    $this->executions->save($attempt);
                }

                return $attempt;
            }

            if (!$exists) {
                $attempt->fail('started_target_missing');
                $this->executions->save($attempt);

                return $attempt;
            }

            $actualHash = @hash_file('sha256', $path);
            if ($actualHash === $attempt->getContentSha256()) {
                $size = filesize($path);
                $attempt->succeed(false === $size ? 0 : $size);
            } else {
                $attempt->fail('recovery_hash_mismatch');
            }
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

    private function assertScopeAllowsLocalFile(Authorization $authorization): void
    {
        $resources = array_map($this->normalizeScopeText(...), $authorization->getResources());
        if (!in_array('local filesystem write access', $resources, true)) {
            throw new \DomainException('The recorded authorization lacks the exact supported capability: Local filesystem write access.');
        }

        $effects = array_map($this->normalizeScopeText(...), $authorization->getEffects());
        if (!in_array('create one local file', $effects, true)
            && !in_array('create one local test file', $effects, true)) {
            throw new \DomainException('The recorded authorization does not permit the supported local-file creation effect.');
        }

        $recognizedLimits = [
            'one new local file only',
            'no overwrite',
            'no external publication',
        ];
        $limits = array_map($this->normalizeScopeText(...), $authorization->getLimits());
        foreach ($limits as $limit) {
            if (!in_array($limit, $recognizedLimits, true)) {
                throw new \DomainException('Execution refused because the authorization contains a limit this executor cannot enforce: '.$limit);
            }
        }
        if (!in_array('one new local file only', $limits, true)
            || !in_array('no overwrite', $limits, true)) {
            throw new \DomainException('Execution requires explicit limits: One new local file only; No overwrite.');
        }
    }

    private function normalizeScopeText(string $text): string
    {
        return mb_strtolower(trim($text, " .\t\n\r\0\x0B"));
    }

    private function validateFilename(string $filename): string
    {
        $filename = trim($filename);
        if (!preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._-]{0,119}\z/', $filename)
            || in_array($filename, ['.', '..'], true)) {
            throw new \DomainException('Filename must be 1-120 safe characters with no path separators.');
        }

        return $filename;
    }
}
