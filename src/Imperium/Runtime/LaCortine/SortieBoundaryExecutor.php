<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final readonly class SortieBoundaryExecutor
{
    public function __construct(
        private IronGate $ironGate,
        private SortieProcessLauncher $launcher,
        private Lazaretto $lazaretto,
    ) {
    }

    public function execute(OutboundRequest $request, \DateTimeImmutable $now): AdmittedExternalArtifact
    {
        if (OutboundExecutionMode::Sortie !== $request->mode) {
            throw new \RuntimeException('SORTIE_EXECUTOR_MODE_MISMATCH: deterministic work cannot create external cognition.');
        }

        $dispatch = $this->ironGate->dispatch($request, $now);
        $raw = $this->launcher->launch($dispatch);

        return $this->lazaretto->admit($raw, $dispatch, new \DateTimeImmutable());
    }
}
