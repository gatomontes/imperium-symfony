<?php

namespace App\Atheneum;

use App\Entity\ExecutionAttempt;
use App\Entity\Interview;
use App\Repository\InterviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/** The first Atheneum service: custody of interview records, without model calls. */
class InterviewRecords
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private InterviewRepository $interviews,
    ) {
    }

    public function create(): Interview
    {
        $interview = new Interview();
        $this->save($interview);

        return $interview;
    }

    public function get(string $id): Interview
    {
        if (!Uuid::isValid($id)) {
            throw new \DomainException('Invalid interview ID.');
        }
        $interview = $this->interviews->find($id);
        if (null === $interview) {
            throw new \DomainException('Interview not found. Use --list to see recent interviews.');
        }
        // A command may wait for human input while another process updates the record.
        $this->entityManager->refresh($interview);

        return $interview;
    }

    public function save(Interview $interview): void
    {
        $this->entityManager->persist($interview);
        $this->entityManager->flush();
    }

    public function delete(Interview $interview): void
    {
        $count = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(attempt.id)')
            ->from(ExecutionAttempt::class, 'attempt')
            ->join('attempt.authorization', 'authorization')
            ->join('authorization.proposal', 'proposal')
            ->where('proposal.interview = :interview')
            ->setParameter('interview', $interview)
            ->getQuery()
            ->getSingleScalarResult();

        if ($count > 0) {
            throw new \DomainException('This mission has execution evidence and cannot be permanently deleted.');
        }

        $this->entityManager->remove($interview);
        $this->entityManager->flush();
    }

    /** @return list<Interview> */
    public function recent(): array
    {
        return $this->interviews->findBy([], ['updatedAt' => 'DESC', 'id' => 'DESC'], 20);
    }
}
