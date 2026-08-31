<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorCreationAuthorityContract as Authority;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorProductionDecisionContract as Decision;
use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorProductionAdoptionBatch2RefusalTest extends TestCase
{
    public function testBatchOneContractsContainAnUnsealableDigestCycle(): void
    {
        self::assertContains('successor_creation_authority', Decision::REQUIRED_FIELDS);
        self::assertSame(
            ['id', 'digest', 'schema'],
            Decision::REQUIRED_AUTHORITY_REFERENCE_FIELDS,
        );
        self::assertContains('source_decision', Authority::REQUIRED_FIELDS);
        self::assertSame(['id', 'digest', 'schema'], Authority::REQUIRED_REFERENCE_FIELDS);
    }

    public function testRefusalNamesTheExactAcyclicCorrection(): void
    {
        $doc = $this->document(
            'docs/provider-binding-successor-production-adoption-batch-2-refusal.md',
        );

        foreach ([
            'BATCH_2_REFUSED_CYCLIC_DECISION_AUTHORITY_DIGEST_DEPENDENCY',
            'No finite construction order exists.',
            'bind an authority issuance target',
            'seal the decision with an authority issuance target',
            'issue and seal the single-use authority from that exact decision and target',
            'atomically consume the authority while creating the successor',
        ] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }
    }

    public function testOnlyAuthorityEmptyBatchOneACorrectionIsNext(): void
    {
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-production-adoption-batch-2-refused.md',
        );

        foreach ([
            'Only Provider Binding Successor Production Adoption Batch 1A authority-empty cyclic-lineage correction contracts may next be considered.',
            'No validator or fixture store is authorized.',
            'may not activate a principal or provider binding',
            'may not issue or consume authority',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'may not migrate a live command',
            'may not open Iron Gate or Lazaretto',
            'The provider binding remains BOUND_INACTIVE.',
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
