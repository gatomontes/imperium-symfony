<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class IronGateEvidenceAuthenticityRemediationBatch11Test extends TestCase
{
    public function testTerminalAuditClosesRemediationWithoutAuthorizingLiveAdoption(): void
    {
        $root = dirname(__DIR__, 3);
        $audit = (string) file_get_contents($root.'/docs/iron-gate-evidence-authenticity-remediation-terminal-audit.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/iron-gate-evidence-authenticity-remediation-campaign-complete.md');

        foreach (['`TERMINAL_THROUGH_BATCH_11`', '`EXISTS_CANONICALLY`', '`DEFERRED_BOUNDARY`', 'trusted-writer integrity only', 'No runtime migration is authorized', 'No remediation batches remain'] as $proof) self::assertStringContainsString($proof, $audit);
        foreach (['terminal through Batch 11', 'Live deterministic', 'consumer adoption remains prohibited', 'No batches remain', 'no such campaign is authorized'] as $boundary) self::assertStringContainsString($boundary, $handoff);
    }

    public function testTerminalAuditPreservesEveryDeferredBoundary(): void
    {
        $root = dirname(__DIR__, 3);
        $audit = (string) file_get_contents($root.'/docs/iron-gate-evidence-authenticity-remediation-terminal-audit.md');
        foreach (['idempotency evidence', 'Hostile-writer hardening', 'distributed persistence', 'live external I/O', 'Iron Gate', 'Lazaretto', 'sortie', 'credential-platform', 'revocation', 'propagation', 'telemetry', 'reassessment', 'containment', 'incident'] as $boundary) self::assertStringContainsString($boundary, $audit);
    }
}
