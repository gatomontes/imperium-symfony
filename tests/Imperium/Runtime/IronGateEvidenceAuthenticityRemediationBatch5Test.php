<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class IronGateEvidenceAuthenticityRemediationBatch5Test extends TestCase
{
    public function testHandoffNamesCompleteReadOnlyChainAndNextBoundary(): void
    {
        $root = dirname(__DIR__, 3);
        $handoff = (string) file_get_contents($root.'/docs/handoffs/iron-gate-evidence-authenticity-remediation-batch-5-complete.md');
        foreach (['Curia occupancy', 'credential attempt', 'callback start', 'response envelope', 'Every reference and digest', 'provider_reinvoked=false', 'Only Batch 6 may next be considered', 'Batch 6 is not authorized'] as $proof) self::assertStringContainsString($proof, $handoff);
    }
}
