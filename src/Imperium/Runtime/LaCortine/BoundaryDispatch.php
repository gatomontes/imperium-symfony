<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final readonly class BoundaryDispatch
{
    public function __construct(
        public string $executionId,
        public string $requestId,
        public string $commissionId,
        public OutboundExecutionMode $mode,
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
