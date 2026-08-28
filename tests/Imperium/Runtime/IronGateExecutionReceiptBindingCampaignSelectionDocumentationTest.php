<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class IronGateExecutionReceiptBindingCampaignSelectionDocumentationTest extends TestCase
{
    public function testSelectionAuthorizesInventoryOnlyAndNamesTheExactUnknownOutcomeGap(): void
    {
        $root = dirname(__DIR__, 3);
        $campaign = (string) file_get_contents($root.'/docs/next-campaign-iron-gate-execution-receipt-binding.md');
        $ready = (string) file_get_contents($root.'/docs/handoffs/iron-gate-execution-receipt-binding-campaign-ready.md');

        self::assertStringContainsString('`BATCH_2_COMPLETE_NO_ELIGIBLE_CONSUMER`', $campaign);
        self::assertStringContainsString('The smallest first migration candidate is the deterministic lane', $campaign);
        foreach (['OutboundRequest', 'IronGate::dispatch()', 'DeterministicBoundaryExecutor', 'CredentialBroker', 'RawExternalPayload', 'Lazaretto', 'Sortie lane'] as $surface) {
            self::assertStringContainsString($surface, $campaign);
        }
        foreach (['provider accepts the effect', 'unknown outcome', 'Automatic replay is unsafe', 'process-local', 'not a durable execution receipt', 'expected return contract'] as $gap) {
            self::assertStringContainsString($gap, $campaign);
        }
        foreach (['`EXISTS_CANONICALLY`', '`EXISTS_FRAGMENTED`', '`ABSENT`', '`DEFERRED_BOUNDARY`'] as $classification) {
            self::assertStringContainsString($classification, $campaign);
        }
        self::assertStringContainsString('Only Preparation Batch 0 is authorized', $ready);
        self::assertStringContainsString('provider-safety assessment Batch 2 are', $campaign);
        self::assertStringContainsString('Preparation Batch 0, which is now complete', $ready);
        self::assertStringContainsString('Batch 1 is', $ready);
        self::assertStringContainsString('No residual Transactional Authority Consumption Adoption batch remains', $ready);
    }

    public function testPreparationKeepsEveryRuntimeAndPerimeterMutationClosed(): void
    {
        $root = dirname(__DIR__, 3);
        $campaign = (string) file_get_contents($root.'/docs/next-campaign-iron-gate-execution-receipt-binding.md');
        $flow = (string) file_get_contents($root.'/docs/delegate-mission-flow.md');

        foreach (['change `OutboundRequest`', 'perform a live external call', 'create a durable claim', 'merge deterministic execution with sortie cognition', 'expand Lazaretto', 'new credential-platform work', 'generalized revocation', 'telemetry', 'containment', 'incidents', 'Delegate Mission Step 70'] as $stop) {
            self::assertStringContainsString($stop, $campaign);
        }
        self::assertStringContainsString('Preparation Batch 0 and', $flow);
        self::assertStringContainsString('has opened no credential', $flow);
        self::assertStringContainsString('Batch 2 proved AgentMail', $flow);
        self::assertStringContainsString('Batch 3 is not authorized', $flow);
    }
}
