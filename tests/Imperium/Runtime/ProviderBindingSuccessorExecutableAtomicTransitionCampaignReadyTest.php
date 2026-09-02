<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorExecutableAtomicTransitionCampaignReadyTest extends TestCase
{
    public function testSelectionOwnsTheStillClosedExecutableBoundary(): void
    {
        $campaign = (string) file_get_contents(dirname(__DIR__, 3).'/docs/next-campaign-provider-binding-successor-executable-atomic-transition.md');
        foreach (['PROVIDER_BINDING_SUCCESSOR_EXECUTABLE_ATOMIC_TRANSITION_SELECTED', 'CAMPAIGN_CLOSURE_ACCEPTED_AFTER_INDEPENDENTLY_ATTESTED_REPROOF', 'PROVIDER_BINDING_SUCCESSOR_ATOMIC_LIVE_TRANSITION_CAMPAIGN_COMPLETE_PRE_EXECUTION_ONLY', 'locally executable, authority-consuming transition', 'UNKNOWN_REPLAY_PROHIBITED'] as $boundary) {
            self::assertStringContainsString($boundary, $campaign, $boundary);
        }
    }

    public function testOnlyPreparationBatchZeroIsAuthorized(): void
    {
        $root = dirname(__DIR__, 3);
        $documents = (string) file_get_contents($root.'/docs/next-campaign-provider-binding-successor-executable-atomic-transition.md').(string) file_get_contents($root.'/docs/handoffs/provider-binding-successor-executable-atomic-transition-campaign-ready.md');
        foreach (['Only Preparation Batch 0 may next be considered', 'Do not implement the proposed contracts', 'separately authorized batches', 'BOUND_INACTIVE', 'NOT_IMPLEMENTED', 'UNKNOWN_REPLAY_PROHIBITED'] as $boundary) {
            self::assertStringContainsString($boundary, $documents, $boundary);
        }
    }

    public function testSequenceKeepsProofAndAuditGatesSeparate(): void
    {
        $campaign = (string) file_get_contents(dirname(__DIR__, 3).'/docs/next-campaign-provider-binding-successor-executable-atomic-transition.md');
        foreach (['Campaign countdown at selection: nine stages including Preparation Batch 0', 'real local contention proof', 'interruption and recovery proof', 'durable receipt and reconstruction', 'adversarial evidence audit', 'separately sequenced terminal Blackquill audit'] as $boundary) {
            self::assertStringContainsStringIgnoringCase($boundary, $campaign, $boundary);
        }
    }

    public function testLocalEntrypointContainsCopyReadyPromptAndProhibitions(): void
    {
        $handoff = (string) file_get_contents(dirname(__DIR__, 3).'/docs/handoffs/provider-binding-successor-executable-atomic-transition-preparation-batch-0-local-ready.md');
        foreach (['git pull --ff-only origin main', 'PROVIDER_BINDING_SUCCESSOR_EXECUTABLE_ATOMIC_TRANSITION_CAMPAIGN_READY', 'PREPARATION_BATCH_0_COMPLETE_EXECUTABLE_ATOMIC_TRANSITION_BOUNDARY_CLASSIFIED', 'ProviderBindingSuccessorExecutableAtomicTransitionPreparationBatch0Test.php', 'New-chat prompt', 'Do not implement an executable contract', 'invoke a provider', 'open Iron Gate or Lazaretto'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff, $boundary);
        }
    }

    public function testFlowPublishesTheSelectedLocalContinuation(): void
    {
        $root = dirname(__DIR__, 3);
        foreach ([(string) file_get_contents($root.'/docs/delegate-mission-flow.md'), (string) file_get_contents($root.'/docs/handoffs/README.md')] as $document) {
            self::assertStringContainsString('provider-binding-successor-executable-atomic-transition-preparation-batch-0-local-ready.md', $document);
        }
    }
}
