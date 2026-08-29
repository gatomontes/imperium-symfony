<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationPrincipalProvenanceCampaignSelectionDocumentationTest extends TestCase
{
    public function testSelectionAuthorizesPreparationBatchZeroOnly(): void
    {
        $root = dirname(__DIR__, 3);
        $campaign = (string) file_get_contents($root.'/docs/next-campaign-provider-binding-activation-principal-provenance-remediation.md');
        foreach (['CAMPAIGN_SELECTED_PREPARATION_BATCH_0_ONLY', 'competent authority', 'exact producer', 'source authority', 'renewal', 'supersession', 'revocation', 'contention', 'crash recovery', 'read-only reconstruction', 'Only Preparation Batch 0 is authorized'] as $proof) {
            self::assertNotFalse(stripos($campaign, $proof), $proof);
        }
    }

    public function testSelectionPreservesTerminalCorridorAndCredentialPerimeter(): void
    {
        $handoff = (string) file_get_contents(dirname(__DIR__, 3).'/docs/handoffs/provider-binding-activation-principal-provenance-remediation-campaign-ready.md');
        foreach (['grants no implementation authority', 'Do not create a principal schema or producer', 'issue caller authority', 'reconsider corridor disposition', 'activate a binding', 'handle a credential', 'external I/O', 'Iron Gate', 'Lazaretto', 'Provider Execution Assurance remains paused'] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }
}
