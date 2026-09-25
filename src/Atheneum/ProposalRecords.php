<?php

namespace App\Atheneum;

use App\Entity\Interview;
use App\Entity\Proposal;
use Doctrine\ORM\EntityManagerInterface;

/** Deterministic custody of saved proposals; no model calls. */
class ProposalRecords
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function latest(Interview $interview): ?Proposal
    {
        return $this->entityManager->getRepository(Proposal::class)->findOneBy(
            ['interview' => $interview],
            ['version' => 'DESC'],
        );
    }

    public function save(Proposal $proposal): void
    {
        $this->entityManager->persist($proposal);
        $this->entityManager->flush();
    }
}
