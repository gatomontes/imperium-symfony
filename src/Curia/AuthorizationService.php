<?php

namespace App\Curia;

use App\Atheneum\AuthorizationRecords;
use App\Atheneum\InterviewRecords;
use App\Atheneum\ProposalRecords;
use App\Entity\Authorization;
use App\Entity\Proposal;
use Symfony\Component\Lock\LockFactory;

class AuthorizationService
{
    public function __construct(
        private InterviewRecords $interviews,
        private ProposalRecords $proposals,
        private AuthorizationRecords $authorizations,
        private LockFactory $lockFactory,
    ) {
    }

    public function request(string $interviewId, string $effectsText): Authorization
    {
        return $this->withLock($interviewId, function (Proposal $proposal) use ($effectsText): Authorization {
            if (Proposal::APPROVED !== $proposal->getStatus()) {
                throw new \DomainException('Proposal approval is required before requesting resource or effect authorization.');
            }
            if (null !== $this->authorizations->forProposal($proposal)) {
                throw new \DomainException('An authorization request already exists for this proposal.');
            }

            $effects = $this->parseEffects($effectsText);
            $authorization = new Authorization($proposal, $effects, $this->executionScopeFor($effects));
            $this->authorizations->save($authorization);

            return $authorization;
        });
    }

    public function decide(string $interviewId, string $authorizationId, bool $authorize): Authorization
    {
        return $this->withLock($interviewId, function (Proposal $proposal) use ($authorizationId, $authorize): Authorization {
            $authorization = $this->authorizations->forProposal($proposal);
            if (null === $authorization || $authorization->getId() !== $authorizationId) {
                throw new \DomainException('The authorization request changed. Reopen the mission before deciding.');
            }
            $authorization->decide($authorize);
            $this->authorizations->save($authorization);

            return $authorization;
        });
    }

    /** @param list<string> $effects
     *  @return array{capability:string,effect:string,root:string,visibility:string,allowedExtensions:list<string>,maxFiles:int,overwrite:bool,maxBytes:int}|null
     */
    private function executionScopeFor(array $effects): ?array
    {
        $normalized = array_map(
            static fn (string $effect): string => mb_strtolower(trim($effect, " .\t\n\r\0\x0B")),
            $effects,
        );

        if (!in_array('create one local file', $normalized, true)
            && !in_array('create one local test file', $normalized, true)) {
            return null;
        }

        return [
            'capability' => 'filesystem.write.public_output',
            'effect' => 'file.create.public',
            'root' => 'public/output',
            'visibility' => 'public',
            'allowedExtensions' => ['txt'],
            'maxFiles' => 1,
            'overwrite' => false,
            'maxBytes' => 32768,
        ];
    }

    /** @return list<string> */
    private function parseEffects(string $text): array
    {
        $text = trim($text);
        if (mb_strlen($text) > 6000) {
            throw new \DomainException('Requested external effects must be 6000 characters or fewer.');
        }
        if ('' === $text) {
            return [];
        }

        $parts = preg_split('/\r?\n|\s*;\s*/u', $text) ?: [];
        $effects = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ('' !== $part) {
                $effects[] = $part;
            }
        }

        return array_values(array_unique($effects));
    }

    /** @param callable(Proposal): Authorization $operation */
    private function withLock(string $interviewId, callable $operation): Authorization
    {
        $lock = $this->lockFactory->createLock('imperium.interview.'.$interviewId);
        if (!$lock->acquire()) {
            throw new \DomainException('This interview is being updated by another process. Try again after it finishes.');
        }

        try {
            $interview = $this->interviews->get($interviewId);
            $proposal = $this->proposals->latest($interview);
            if (null === $proposal) {
                throw new \DomainException('No proposal exists for this mission.');
            }

            return $operation($proposal);
        } finally {
            $lock->release();
        }
    }
}
