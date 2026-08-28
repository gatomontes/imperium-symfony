<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class TransactionalAuthorityConsumptionCampaignCloseoutDocumentationTest extends TestCase
{
    public function testCampaignClosesTerminalWithoutPromotingResidualConsumers(): void
    {
        $root = dirname(__DIR__, 3);
        $campaign = (string) file_get_contents($root.'/docs/next-campaign-transactional-authority-consumption.md');
        $contract = (string) file_get_contents($root.'/docs/transactional-authority-consumption-contract.md');
        $closeout = (string) file_get_contents($root.'/docs/handoffs/transactional-authority-consumption-campaign-complete.md');
        $flow = (string) file_get_contents($root.'/docs/delegate-mission-flow.md');

        self::assertStringContainsString('`TERMINAL_THROUGH_BATCH_13`', $campaign);
        self::assertStringContainsString('`TERMINAL_THROUGH_BATCH_13`', $contract);
        foreach (['482', '371', '231', '26', '3', '202', '9', '1'] as $count) {
            self::assertStringContainsString($count, $closeout);
        }
        foreach (['`TRANSACTIONAL_CANONICAL`', '`LOCKED_FRAGMENTED`', '`RACE_EXPOSED`', '`RECOVERY_INCOMPLETE`', '`DEFERRED_EXTERNAL_BOUNDARY`'] as $posture) {
            self::assertStringContainsString($posture, $closeout);
        }
        self::assertStringContainsString('Twenty-six canonical consumers are not a canonical runtime', $closeout);
        self::assertStringContainsString('Runtime behavior is unchanged', $closeout);
        self::assertStringContainsString('No batches remain', $campaign);
        self::assertStringContainsString('Transactional Authority Consumption Adoption is terminal through Batch 13', $flow);
    }

    public function testCloseoutSelectsPreparationOnlyAndKeepsPerimeterClosed(): void
    {
        $root = dirname(__DIR__, 3);
        $closeout = (string) file_get_contents($root.'/docs/handoffs/transactional-authority-consumption-campaign-complete.md');
        $ready = (string) file_get_contents($root.'/docs/handoffs/iron-gate-execution-receipt-binding-campaign-ready.md');
        $index = (string) file_get_contents($root.'/docs/handoffs/README.md');

        foreach ([$closeout, $ready] as $document) {
            self::assertStringContainsString('Iron Gate Execution Authority and Receipt Binding', $document);
            self::assertStringContainsString('Preparation Batch 0', $document);
        }
        foreach (['external call', 'credential', 'Lazaretto', 'sortie', 'revocation', 'telemetry', 'containment', 'incident'] as $boundary) {
            self::assertStringContainsString($boundary, $ready);
        }
        self::assertStringContainsString('Batch 10 is complete through accepted receipt', $index);
        self::assertStringContainsString('Batch 11 is not authorized', $index);
    }
}
