<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ContinuousAgentGovernanceCampaignCloseoutDocumentationTest extends TestCase
{
    public function testCampaignIsTerminalAndDeferredPerimeterRemainsClosed(): void
    {
        $root = dirname(__DIR__, 3);
        $handoff = (string) file_get_contents($root.'/docs/handoffs/continuous-agent-governance-controls-campaign-complete.md');
        $campaign = (string) file_get_contents($root.'/docs/next-campaign-continuous-agent-governance.md');
        $index = (string) file_get_contents($root.'/docs/handoffs/README.md');
        $todo = (string) file_get_contents($root.'/todo/continuous-agent-governance-controls.md');

        self::assertStringContainsString('complete through Batch 16', $handoff);
        self::assertStringContainsString('`TERMINAL_THROUGH_BATCH_16`', $campaign);
        self::assertStringContainsString('No next runtime implementation campaign is selected', $handoff);
        self::assertStringContainsString('Operational Cognition Lease Interruption is terminal through Batch 6', $index);
        self::assertStringContainsString('Transactional Authority Consumption Adoption is complete through Batch 8', $index);
        self::assertStringContainsString('separately prepared campaign', $todo);
        foreach (['generalized revocation propagation', 'telemetry', 'containment', 'incident handling', 'Iron Gate', 'Lazaretto', 'sorties', 'new credential-platform work'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
        foreach (['Delegate Mission remains terminal at Step 69', 'Runtime Integrity Hardening at Step 35', 'credential-boundary remediation at Batch 17', 'Institutional Decision Integrity at Batch 6'] as $closed) {
            self::assertStringContainsString($closed, $handoff);
        }
    }
}
