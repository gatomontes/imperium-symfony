<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class AtomicTransitionIndependentlyVerifiableReproofCampaignReadyTest extends TestCase
{
    public function testSelectionIsDistinctFromV1(): void
    {
        $campaign = (string) file_get_contents(dirname(__DIR__, 3).'/docs/next-campaign-atomic-transition-independently-verifiable-reproof.md');
        foreach (['ATOMIC_TRANSITION_INDEPENDENTLY_VERIFIABLE_REPROOF_SELECTED', 'acceptance-case inputs and observations were never retained', 'distinct v2 evidence event', 'may never be presented as rehabilitation of the v1 receipt', 'CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT', 'UNKNOWN_REPLAY_PROHIBITED'] as $boundary) {
            self::assertStringContainsString($boundary, $campaign, $boundary);
        }
    }

    public function testOnlyPreparationIsAuthorized(): void
    {
        $root = dirname(__DIR__, 3);
        $documents = (string) file_get_contents($root.'/docs/handoffs/atomic-transition-independently-verifiable-reproof-campaign-ready.md').(string) file_get_contents($root.'/docs/next-campaign-atomic-transition-independently-verifiable-reproof.md');
        foreach (['ATOMIC_TRANSITION_INDEPENDENTLY_VERIFIABLE_REPROOF_CAMPAIGN_READY', 'Only Preparation Batch 0 may next be considered', 'Do not implement the proposed contracts', 'Batch 5 execution', 'Batch 6 verification/signing', 'Batch 8 terminal audit', 'separately authorized and separately sequenced'] as $boundary) {
            self::assertStringContainsString($boundary, $documents, $boundary);
        }
    }

    public function testLocalEntrypointPinsScope(): void
    {
        $handoff = (string) file_get_contents(dirname(__DIR__, 3).'/docs/handoffs/atomic-transition-independently-verifiable-reproof-preparation-batch-0-local-ready.md');
        foreach (['git pull --ff-only origin main', 'git merge-base --is-ancestor 1ac9ede HEAD', 'PREPARATION_BATCH_0_COMPLETE_INDEPENDENTLY_VERIFIABLE_REPROOF_BOUNDARY_CLASSIFIED', 'AtomicTransitionIndependentlyVerifiableReproofPreparationBatch0Test.php', 'Only Preparation Batch 0 may be performed', 'New-chat prompt', 'Do not implement v2', 'remove the closure qualification'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff, $boundary);
        }
    }

    public function testFlowPublishesLocalContinuationAndCountdown(): void
    {
        $root = dirname(__DIR__, 3);
        $flow = (string) file_get_contents($root.'/docs/delegate-mission-flow.md');
        $campaign = (string) file_get_contents($root.'/docs/next-campaign-atomic-transition-independently-verifiable-reproof.md');

        self::assertStringContainsString('atomic-transition-independently-verifiable-reproof-preparation-batch-0-local-ready.md', $flow);
        self::assertStringContainsString('Campaign countdown at selection is nine stages including Preparation Batch 0', $flow);
        self::assertStringContainsString('Campaign countdown at selection: nine stages including Preparation Batch 0', $campaign);
        self::assertStringContainsString('authorizes Preparation Batch 0 only', $campaign);
    }

    public function testPreparationForbidsOperationalActions(): void
    {
        $handoff = (string) file_get_contents(dirname(__DIR__, 3).'/docs/handoffs/atomic-transition-independently-verifiable-reproof-campaign-ready.md');
        foreach (['Do not inspect private operator-local material', 'rerun or execute a mission', 'execute a verifier', 'create or use signing material', 'invoke a provider', 'perform external I/O', 'repair or replace v1', 'close the campaign'] as $prohibition) {
            self::assertStringContainsString($prohibition, $handoff, $prohibition);
        }
    }
}
