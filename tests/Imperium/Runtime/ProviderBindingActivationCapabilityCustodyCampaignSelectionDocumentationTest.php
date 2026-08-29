<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationCapabilityCustodyCampaignSelectionDocumentationTest extends TestCase
{
    public function testNextCampaignBeginsWithPreparationOnlyAndKeepsExecutionClosed(): void
    {
        $root = dirname(__DIR__, 3);
        $campaign = (string) file_get_contents($root.'/docs/next-campaign-provider-binding-activation-capability-custody.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/provider-binding-activation-capability-custody-campaign-ready.md');
        $flow = (string) file_get_contents($root.'/docs/delegate-mission-flow.md');

        foreach (['`CAMPAIGN_SELECTED_PREPARATION_BATCH_0_ONLY`', 'Only Preparation Batch 0 is authorized', 'provider-binding activation', 'cross-process', 'Refusal is a valid preparation result'] as $proof) self::assertStringContainsString($proof, $campaign);
        foreach (['Only Preparation Batch 0', 'New-chat continuation', 'Provider Execution Assurance remains paused'] as $proof) self::assertStringContainsString($proof, $handoff);
        foreach (['Governed Tool and Provider Separation is terminal through Batch 9', 'Provider Binding Activation and Capability Custody', 'Preparation Batch 0 is authorized'] as $proof) self::assertStringContainsString($proof, $flow);
        foreach (['activate a provider binding', 'issue or resolve credentials', 'invoke a provider', 'external I/O', 'Iron Gate', 'Lazaretto'] as $boundary) self::assertNotFalse(stripos($campaign.$handoff, $boundary), $boundary);
    }
}
