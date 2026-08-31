<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorProductionRealizationCampaignReadyTest extends TestCase
{
    public function testOnlyPreparationBatchZeroIsSelected(): void
    {
        $selection = $this->document('docs/next-campaign-provider-binding-successor-production-realization.md');
        $handoff = $this->document('docs/handoffs/provider-binding-successor-production-realization-campaign-ready.md');

        self::assertStringContainsString('Begin Provider Binding Successor Production Realization Preparation Batch 0 only.', $selection);
        self::assertStringContainsString('Preparation Batch 0 only is', $handoff);
        self::assertStringContainsString('eight campaign batches including Preparation', $handoff);
    }

    public function testPreparationInventoryNamesEveryRequiredBoundary(): void
    {
        $selection = $this->document('docs/next-campaign-provider-binding-successor-production-realization.md');

        foreach ([
            'exact competent production-decision owner and executor principal',
            'production decision issuer and immutable decision lineage',
            'single-use successor-creation authority issuance and custody',
            'authority consumption and immutable successor creation as one atomic winner',
            'v3 execution-admission seam and explicit adoption target',
            'crash recovery, replay, contention, expiry, revocation and reconstruction',
            'process-local capability identity and durable authority separation',
            'credential, secret and provider-effect exclusion',
            'threat-model assumptions, candidate boundary postures and non-authorities',
            'EXISTS_CANONICALLY',
            'EXISTS_FRAGMENTED',
            'ABSENT',
            'DEFERRED_BOUNDARY',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $selection);
        }
    }

    public function testCampaignReadyHandoffPreservesTheClosedRuntimePerimeter(): void
    {
        $handoff = $this->document('docs/handoffs/provider-binding-successor-production-realization-campaign-ready.md');

        foreach ([
            'may not define a runtime contract or change runtime behavior',
            'may not produce a decision, issue or consume authority, create a successor',
            'may not activate a principal or provider binding',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'may not migrate a live command',
            'may not open Iron Gate or Lazaretto',
            'The provider binding remains BOUND_INACTIVE.',
            'The required v3 execution admission remains NOT_IMPLEMENTED.',
            'UNKNOWN_REPLAY_PROHIBITED remains binding.',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function document(string $path): string
    {
        $contents = file_get_contents(dirname(__DIR__, 3).'/'.$path);
        self::assertNotFalse($contents);

        return $contents;
    }
}
