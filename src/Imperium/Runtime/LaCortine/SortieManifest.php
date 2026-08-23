<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final readonly class SortieManifest
{
    /**
     * @param list<string> $destinations
     * @param list<string> $toolIds
     * @param list<string> $capabilityIds
     */
    public function __construct(
        public string $executionId,
        public string $sortieId,
        public string $manifestationId,
        public string $commissionId,
        public string $authorizationId,
        public string $objective,
        public string $contextDigest,
        public array $destinations,
        public array $toolIds,
        public array $capabilityIds,
        public string $expectedReturnContract,
        public \DateTimeImmutable $expiresAt,
    ) {
        foreach ([$executionId, $sortieId, $manifestationId, $commissionId, $authorizationId, $objective, $contextDigest, $expectedReturnContract] as $value) {
            if ('' === trim($value)) {
                throw new \InvalidArgumentException('Sortie execution, identity, authority, objective, context, and return-contract lineage are mandatory.');
            }
        }
    }
}
