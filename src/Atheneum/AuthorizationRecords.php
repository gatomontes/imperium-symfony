<?php

namespace App\Atheneum;

use App\Entity\Authorization;
use App\Entity\Proposal;
use Doctrine\ORM\EntityManagerInterface;

/** Deterministic custody of authorization records; no model calls or execution. */
class AuthorizationRecords
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function forProposal(Proposal $proposal): ?Authorization
    {
        return $this->entityManager->getRepository(Authorization::class)->findOneBy(['proposal' => $proposal]);
    }

    public function save(Authorization $authorization): void
    {
        $this->entityManager->persist($authorization);
        $this->entityManager->flush();
    }
}
