<?php

namespace App\Atheneum;

use App\Entity\Authorization;
use App\Entity\ExecutionAttempt;
use Doctrine\ORM\EntityManagerInterface;

/** Deterministic custody of execution evidence. */
class ExecutionRecords
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function forAuthorization(Authorization $authorization): ?ExecutionAttempt
    {
        return $this->entityManager->getRepository(ExecutionAttempt::class)->findOneBy(['authorization' => $authorization]);
    }

    public function save(ExecutionAttempt $attempt): void
    {
        $this->entityManager->persist($attempt);
        $this->entityManager->flush();
    }
}
