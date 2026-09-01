<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorAtomicLiveTransitionCampaignReadyTest extends TestCase
{
    public function testSelectionAuthorizesPreparationBatchZeroOnly(): void
    {
        $selection = $this->document(
            'docs/next-campaign-provider-binding-successor-atomic-live-transition.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-atomic-live-transition-campaign-ready.md',
        );

        $authority = 'Only Provider Binding Successor Atomic Live Transition Preparation Batch 0 is authorized';

        self::assertStringContainsString($authority, $selection);
        self::assertStringContainsString($authority, $handoff);
        self::assertStringContainsString(
            'PROVIDER_BINDING_SUCCESSOR_LIVE_ADOPTION_CAMPAIGN_COMPLETE_PRE_LIVE_TRANSITION_ONLY',
            $selection,
        );
        self::assertStringContainsString(
            'nine batches including Preparation Batch 0',
            $selection,
        );
    }

    public function testPreparationInventoriesTheCompleteExecutableTransition(): void
    {
        $selection = $this->document(
            'docs/next-campaign-provider-binding-successor-atomic-live-transition.md',
        );

        foreach ([
            'exact runtime entry point, competent transition decision owner and executor principal',
            'single-use live-transition authority issuance, durable custody and process-local delivery',
            'combined same-root winner boundary',
            'exact BOUND_INACTIVE source binding and successor target state',
            'persistence roots, lock scope, lock order and transaction primitive',
            'one atomic commit',
            'first irreversible write and every crash cut before and after it',
            'exact replay, changed evidence, same-root contention, expiry and revocation',
            'partial-write refusal, deterministic recovery and read-only reconstruction',
            'durable transition receipt, audit lineage and proof digest',
            'credential, secret and process-local capability exclusion',
            'final closed boundary before credential resolution, provider invocation, external I/O and provider effect start',
            'EXISTS_CANONICALLY',
            'EXISTS_FRAGMENTED',
            'ABSENT',
            'DEFERRED_BOUNDARY',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $selection);
        }
    }

    public function testCampaignReadyHandoffPreservesTheClosedEffectPerimeter(): void
    {
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-atomic-live-transition-campaign-ready.md',
        );

        foreach ([
            'may not define an executable runtime contract or change runtime behavior',
            'may not produce a decision',
            'may not issue or consume live authority',
            'may not admit execution',
            'may not adopt a successor or change binding state',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'may not start a provider effect',
            'may not authorize retry',
            'may not migrate a live command',
            'may not open Iron Gate or Lazaretto',
            'The provider binding remains BOUND_INACTIVE.',
            'The v3 execution admission remains NOT_IMPLEMENTED.',
            'UNKNOWN_REPLAY_PROHIBITED remains binding.',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function document(string $path): string
    {
        return (string) preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(dirname(__DIR__, 3).'/'.$path),
        );
    }
}
