<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final readonly class BoundaryDispatch
{
    /**
     * @param list<string> $allowedToolIds
     * @param list<string> $allowedCapabilityIds
     */
    public function __construct(
        public string $executionId,
        public string $requestId,
        public string $commissionId,
        public string $authorizationId,
        public OutboundExecutionMode $mode,
        public array $allowedToolIds,
        public array $allowedCapabilityIds,
        public string $expectedReturnContract,
        public ?SortieManifest $sortie,
    ) {
        if (OutboundExecutionMode::Sortie === $mode && null === $sortie) {
            throw new \InvalidArgumentException('A sortie execution requires an exact sortie manifest.');
        }
        if (OutboundExecutionMode::Deterministic === $mode && null !== $sortie) {
            throw new \InvalidArgumentException('Deterministic execution must not create cognitive agency.');
        }
    }
}
