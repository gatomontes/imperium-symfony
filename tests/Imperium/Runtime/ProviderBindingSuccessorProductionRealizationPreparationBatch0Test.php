<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorProductionRealizationPreparationBatch0Test extends TestCase
{
    public function testInventoryClassifiesEveryProductionRealizationBoundary(): void
    {
        $document = $this->document('docs/provider-binding-successor-production-realization-preparation-inventory.md');

        foreach ([
            'PREPARATION_BATCH_0_COMPLETE_PRODUCTION_REALIZATION_BOUNDARIES_CLASSIFIED',
            'competent production-decision owner',
            'exact executor principal',
            'production decision issuer',
            'authority issuer and durable custody',
            'process-local capability identity',
            'atomic authority-consumption and successor-creation winner',
            'effect-start ordering',
            'v3 execution-admission contract and validator',
            'production adoption decision and join',
            'crash recovery',
            'replay and same-root contention',
            'expiry and revocation',
            'read-only reconstruction',
            'secret exclusion',
            'threat-model alignment',
            'credential possession and provider execution',
            'EXISTS_CANONICALLY',
            'EXISTS_FRAGMENTED',
            'ABSENT',
            'DEFERRED_BOUNDARY',
        ] as $finding) {
            self::assertStringContainsString($finding, $document);
        }
    }

    public function testInventoryRejectsAuthorityAndEffectConflation(): void
    {
        $document = $this->document('docs/provider-binding-successor-production-realization-preparation-inventory.md');

        foreach ([
            'promoting offline fixtures or reconstruction into production records',
            'letting credential possession imply execution authority',
            'treating process-local capability identity as durable authority',
            'consuming authority before the immutable successor winner can be committed',
            'combining successor creation with credential resolution or provider I/O',
        ] as $rejection) {
            self::assertStringContainsString($rejection, $document);
        }
    }

    public function testHandoffAuthorizesBatchOneContractsOnly(): void
    {
        $handoff = $this->document('docs/handoffs/provider-binding-successor-production-realization-preparation-batch-0-complete.md');

        foreach ([
            'Only Provider Binding Successor Production Realization Batch 1 authority-empty production-decision issuer and exact principal contracts may next be considered.',
            'may define separately versioned contracts and pure validators only',
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

    public function testPreparationChangesNoRuntimeSource(): void
    {
        $source = file_get_contents(__FILE__);
        self::assertNotFalse($source);
        self::assertStringNotContainsString('src/Imperium/Runtime', $source);
    }

    private function document(string $path): string
    {
        $contents = file_get_contents(dirname(__DIR__, 3).'/'.$path);
        self::assertNotFalse($contents);

        return $contents;
    }
}
