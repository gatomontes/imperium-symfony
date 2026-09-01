<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

/** @deprecated Historical self-recomputed closure; permanently disabled. */
final readonly class AtomicTransitionEvidenceCorrectedClosureService
{
    public function __construct(private AtomicTransitionEvidenceTerminalRecomputer $recomputer)
    {
    }

    public function close(
        string $closureId,
        array $cases,
        array $results,
        array $manifest,
        array $secretProof,
        array $aggregateReceipt,
        array $terminalRecomputation,
    ): array {
        throw new \RuntimeException('PBL1016_HISTORICAL_SELF_RECOMPUTED_CLOSURE_DISABLED');
    }
}
