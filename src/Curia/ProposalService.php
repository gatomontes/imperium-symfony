<?php

namespace App\Curia;

use App\Atheneum\InterviewRecords;
use App\Atheneum\ProposalRecords;
use App\Entity\Interview;
use App\Entity\Proposal;
use Symfony\Component\Lock\LockFactory;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ProposalService
{
    public function __construct(
        private InterviewRecords $interviews,
        private ProposalRecords $proposals,
        private ProposalDrafter $drafter,
        private LockFactory $lockFactory,
    ) {
    }

    public function generate(string $interviewId): Proposal
    {
        $lock = $this->lockFactory->createLock('imperium.interview.'.$interviewId);
        if (!$lock->acquire()) {
            throw new \DomainException('This interview is being updated by another process. Try again after it finishes.');
        }

        try {
            $interview = $this->interviews->get($interviewId);
            if (Interview::DRAFT_AUTHORIZED !== $interview->getStatus()) {
                throw new \DomainException('Drafting permission is required before generating a proposal.');
            }
            if (null !== $this->proposals->latest($interview)) {
                throw new \DomainException('A saved proposal already exists. Reopen the interview to review it.');
            }

            $this->drafter->assertConfigured();
            try {
                $draft = $this->drafter->draft($interview);
            } catch (\Throwable $error) {
                throw new \DomainException('No proposal was saved. '.$this->failureHint($error).' Choose Generate proposal again to retry explicitly; another attempt may incur provider charges.');
            }

            $proposal = new Proposal($interview, 1, $interview->getVersion(), $draft->toArray());
            $this->proposals->save($proposal);

            return $proposal;
        } finally {
            $lock->release();
        }
    }

    public function revise(string $interviewId, int $observedVersion, string $guidance): Proposal
    {
        $lock = $this->lockFactory->createLock('imperium.interview.'.$interviewId);
        if (!$lock->acquire()) {
            throw new \DomainException('This interview is being updated by another process. Try again after it finishes.');
        }

        try {
            $interview = $this->interviews->get($interviewId);
            $current = $this->proposals->latest($interview);
            if (null === $current) {
                throw new \DomainException('No saved proposal exists to revise.');
            }
            if ($current->getVersion() !== $observedVersion) {
                throw new \DomainException('The proposal changed. Review the latest version before requesting a revision.');
            }
            if (Proposal::DRAFT !== $current->getStatus()) {
                throw new \DomainException('An approved proposal cannot be revised in place.');
            }

            $this->drafter->assertConfigured();
            try {
                $draft = $this->drafter->revise($interview, $current, $guidance);
            } catch (\Throwable $error) {
                throw new \DomainException('No revised proposal was saved. '.$this->failureHint($error).' Request the revision again explicitly; another attempt may incur provider charges.');
            }

            $proposal = new Proposal(
                $interview,
                $current->getVersion() + 1,
                $interview->getVersion(),
                $draft->toArray(),
            );
            $this->proposals->save($proposal);

            return $proposal;
        } finally {
            $lock->release();
        }
    }

    public function approve(string $interviewId, int $observedVersion): Proposal
    {
        $lock = $this->lockFactory->createLock('imperium.interview.'.$interviewId);
        if (!$lock->acquire()) {
            throw new \DomainException('This interview is being updated by another process. Try again after it finishes.');
        }

        try {
            $interview = $this->interviews->get($interviewId);
            $proposal = $this->proposals->latest($interview);
            if (null === $proposal) {
                throw new \DomainException('No saved proposal exists to approve.');
            }
            if ($proposal->getVersion() !== $observedVersion) {
                throw new \DomainException('The proposal changed. Review the latest version before approving it.');
            }
            $proposal->approve();
            $this->proposals->save($proposal);

            return $proposal;
        } finally {
            $lock->release();
        }
    }

    private function failureHint(\Throwable $error): string
    {
        do {
            if ($error instanceof InvalidProposalDraft) {
                return $error->getMessage();
            }
            if ($error instanceof HttpExceptionInterface) {
                $status = $error->getResponse()->getStatusCode();

                return match ($status) {
                    401 => 'HTTP 401. Check DEEPSEEK_API_KEY in .env.local.',
                    402 => 'HTTP 402. Check your DeepSeek API balance.',
                    403 => 'HTTP 403. Check API access permissions on your DeepSeek account.',
                    404 => 'HTTP 404. Check the configured DeepSeek model and endpoint.',
                    429 => 'HTTP 429. DeepSeek is rate-limiting requests.',
                    default => $status >= 500 ? 'DeepSeek is unavailable. Try again later.' : 'The provider returned HTTP '.$status.'.',
                };
            }
            if ($error instanceof TransportExceptionInterface) {
                return 'Could not reach DeepSeek. Check your connection and PHP TLS/certificate configuration.';
            }

            $previous = $error->getPrevious();
            if (null === $previous) {
                return 'Proposal processing failed.';
            }
            $error = $previous;
        } while (true);
    }
}
