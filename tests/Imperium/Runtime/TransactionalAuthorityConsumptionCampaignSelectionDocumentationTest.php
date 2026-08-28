<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class TransactionalAuthorityConsumptionCampaignSelectionDocumentationTest extends TestCase
{
    public function testSelectionHistoryAndCurrentBatchKeepRuntimeAndExternalBoundariesClosed(): void
    {
        $root = dirname(__DIR__, 3);
        $campaign = (string) file_get_contents($root.'/docs/next-campaign-transactional-authority-consumption.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/transactional-authority-consumption-campaign-ready.md');
        $flow = (string) file_get_contents($root.'/docs/delegate-mission-flow.md');
        $index = (string) file_get_contents($root.'/docs/handoffs/README.md');

        self::assertStringContainsString('`BATCH_6_DETERMINISTIC_DELEGATE_SENATE_ADOPTED_BATCH_7_NOT_AUTHORIZED`', $campaign);
        self::assertStringContainsString('Only Preparation Batch 0 is', $handoff);
        self::assertStringContainsString('No implementation step is authorized merely because it is listed', $campaign);
        foreach (['`EXISTS_CANONICALLY`', '`EXISTS_FRAGMENTED`', '`ABSENT`', '`DEFERRED_BOUNDARY`'] as $classification) {
            self::assertStringContainsString($classification, $campaign);
        }
        foreach (['`TRANSACTIONAL_CANONICAL`', '`LOCKED_FRAGMENTED`', '`RACE_EXPOSED`', '`RECOVERY_INCOMPLETE`', '`DEFERRED_EXTERNAL_BOUNDARY`'] as $posture) {
            self::assertStringContainsString($posture, $campaign);
        }
        foreach (['Generalized revocation', 'telemetry', 'containment', 'incidents', 'Iron Gate execution', 'Lazaretto expansion', 'sorties', 'new credential-platform work'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
        self::assertStringContainsString('Batch 7 remains unopened pending explicit authorization', $flow);
        self::assertStringContainsString('Transactional Authority Consumption Adoption is complete through Batch 6', $index);
    }
}
