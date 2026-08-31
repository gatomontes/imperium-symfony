<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorLiveAdoptionCampaignReadyTest extends TestCase
{
    public function testSelectionAuthorizesPreparationBatchZeroOnly(): void
    {
        $selection = $this->document(
            'docs/next-campaign-provider-binding-successor-live-adoption.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-live-adoption-campaign-ready.md',
        );

        $authority = 'Only Provider Binding Successor Live Adoption Preparation Batch 0 is authorized';

        self::assertStringContainsString($authority, $selection);
        self::assertStringContainsString($authority, $handoff);
        self::assertStringContainsString(
            'PROVIDER_BINDING_SUCCESSOR_PRODUCTION_REALIZATION_CAMPAIGN_COMPLETE_PRE_PROVIDER_EFFECT_ONLY',
            $selection,
        );
    }

    public function testPreparationInventoriesTheCompleteAdoptionBoundary(): void
    {
        $selection = $this->document(
            'docs/next-campaign-provider-binding-successor-live-adoption.md',
        );

        foreach ([
            'competent live-adoption decision owner and executor principal',
            'single-use live-adoption authority issuer, custody and consumer',
            'successor-to-v3 join required at entry',
            'v3 execution-admission ownership',
            'one atomic same-root winner',
            'original BOUND_INACTIVE implementation binding',
            'crash cuts before and after the immutable winner',
            'replay, changed evidence, same-root contention, expiry and revocation',
            'read-only reconstruction and durable secret exclusion',
            'final boundary before credential resolution, provider invocation, external I/O and provider effect start',
            'EXISTS_CANONICALLY',
            'EXISTS_FRAGMENTED',
            'ABSENT',
            'DEFERRED_BOUNDARY',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $selection);
        }
    }

    public function testSelectionPreservesTheClosedProviderEffectPerimeter(): void
    {
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-live-adoption-campaign-ready.md',
        );

        foreach ([
            'may not define a live-adoption runtime contract or change runtime behavior',
            'may not decide or perform live adoption',
            'may not admit execution',
            'may not issue or consume live-adoption authority',
            'may not create or activate a live successor binding',
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
