<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderExecutionAssuranceCampaignSelectionDocumentationTest extends TestCase
{
    public function testSelectionAuthorizesPreparationOnlyAndNamesExactUnknownOutcomeEvidence(): void
    {
        $root = dirname(__DIR__, 3);
        $campaign = (string) file_get_contents($root.'/docs/next-campaign-provider-execution-assurance.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/provider-execution-assurance-campaign-ready.md');
        foreach (['`CAMPAIGN_SELECTED_PREPARATION_BATCH_0_ONLY`', 'Only Preparation Batch 0 is authorized', 'collision domain and retention interval', 'query-before-retry', 'Refusal is a valid', '`EXISTS_CANONICALLY`', '`EXISTS_FRAGMENTED`', '`ABSENT`', '`DEFERRED_BOUNDARY`'] as $proof) self::assertStringContainsString($proof, $campaign);
        foreach (['Provider Execution Assurance', 'Only Preparation Batch 0 is authorized', 'New-chat continuation', 'Do not perform external I/O'] as $proof) self::assertStringContainsString($proof, $handoff);
    }

    public function testSelectionKeepsEveryLiveAndDeferredBoundaryClosed(): void
    {
        $root = dirname(__DIR__, 3);
        $campaign = (string) file_get_contents($root.'/docs/next-campaign-provider-execution-assurance.md');
        foreach (['network I/O', 'Iron Gate', 'Lazaretto', 'sortie', 'credential-platform', 'revocation', 'propagation', 'telemetry', 'reassessment', 'containment', 'incident', 'live-adoption', 'hostile-writer', 'distributed persistence'] as $boundary) self::assertStringContainsString($boundary, $campaign);
    }
}

