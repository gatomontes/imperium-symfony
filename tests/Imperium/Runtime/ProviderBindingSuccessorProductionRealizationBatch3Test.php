<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorCreationAuthorityV2Contract as Authority;
use App\Imperium\Runtime\LaCortine\ProviderBindingActivationReconciledLifecycleSuccessorContract as Successor;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicCreationInertSeam;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicCreationWinnerBoundaryContract as Boundary;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicCreationWinnerBoundaryValidator as Validator;
use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorProductionRealizationBatch3Test extends TestCase
{
    public function testExactSameRootBoundaryClassifiesReadyAndInert(): void
    {
        $boundary = $this->fixture();
        (new Validator())->assert($boundary);
        $result = (new ProviderBindingSuccessorAtomicCreationInertSeam())->inspect($boundary);

        self::assertSame('READY_INERT_ATOMIC_BOUNDARY', $result['classification']);
        self::assertSame(Boundary::LOCK_KIND, $result['lock_kind']);
        self::assertTrue($result['consumption_and_creation_atomic']);
        self::assertFalse($result['authority_consumed']);
        self::assertFalse($result['successor_created']);
        self::assertFalse($result['partial_record_created']);
        self::assertFalse($result['effect_started']);
    }

    public function testClaimedConsumptionOrCreationRefuses(): void
    {
        $boundary = $this->fixture();
        $boundary['authority_consumed'] = true;
        $boundary = $this->seal($boundary);

        $this->expectExceptionMessage('PBR300_ATOMIC_SUCCESSOR_WINNER_BOUNDARY_INVALID');
        (new Validator())->assert($boundary);
    }

    public function testSecretBearingBoundaryRefuses(): void
    {
        $boundary = $this->fixture();
        $boundary['successor_target']['credential_reference'] = 'env://forbidden';
        $boundary = $this->seal($boundary);

        $this->expectExceptionMessage('PBR300_ATOMIC_SUCCESSOR_WINNER_BOUNDARY_INVALID');
        (new Validator())->assert($boundary);
    }

    public function testInertSeamHasNoPersistenceOrAuthorityConsumptionDependency(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 3).'/src/Imperium/Runtime/LaCortine/ProviderBindingSuccessorAtomicCreationInertSeam.php',
        );
        self::assertNotFalse($source);

        foreach ([
            'AtomicTransition',
            'AuthorityConsumptionStore',
            'ImmutableRecordStore',
            '->put(',
            '->consume(',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testContractGrantsNoAuthority(): void
    {
        foreach (Boundary::NON_AUTHORITIES as $name => $value) {
            self::assertFalse($value, $name);
        }

        self::assertFalse(Boundary::INVARIANTS['authority_consumed']);
        self::assertFalse(Boundary::INVARIANTS['successor_created']);
        self::assertFalse(Boundary::INVARIANTS['effect_started']);
    }

    public function testDocumentationAuthorizesV3ContractOnly(): void
    {
        $doc = $this->document('docs/provider-binding-successor-production-realization-batch-3-inert-atomic-seam.md');
        $handoff = $this->document('docs/handoffs/provider-binding-successor-production-realization-batch-3-complete.md');

        foreach ([
            'BATCH_3_INERT_SAME_ROOT_ATOMIC_CONSUMPTION_AND_SUCCESSOR_CREATION_BOUNDARY_COMPLETE',
            'The required lock kind is exact_replay_contention_root.',
            'Authority consumption and successor creation must be one atomic commit.',
            'A crash before commit must leave no consumption and no successor.',
            'No partial consumption or successor record may exist.',
        ] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }

        foreach ([
            'Only Provider Binding Successor Production Realization Batch 4 v3 execution-admission contract and fail-closed validator may next be considered.',
            'may define an authority-empty v3 contract and pure validator only',
            'may not admit execution, issue or consume authority, create a successor',
            'may not activate a principal or provider binding',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'may not migrate a live command',
            'may not open Iron Gate or Lazaretto',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function fixture(): array
    {
        return $this->seal([
            'schema' => Boundary::SCHEMA,
            'winner_boundary_id' => 'successor-atomic-winner-boundary.1',
            'instance_id' => 'instance.1',
            'authority_schema' => Authority::SCHEMA,
            'authority_source' => [
                'id' => 'successor-creation-authority.1',
                'digest' => str_repeat('a', 64),
                'schema' => Authority::SCHEMA,
            ],
            'custody_source' => [
                'id' => 'successor-authority-custody.1',
                'digest' => str_repeat('b', 64),
                'schema' => 'imperium.clavium.provider-binding-successor-creation-authority-durable-custody-boundary/v1',
            ],
            'successor_target' => [
                'id' => 'binding-successor-target.1',
                'digest' => str_repeat('c', 64),
                'schema' => Successor::SCHEMA,
            ],
            'replay_contention_root' => 'binding-reconciliation-root.1',
            'lock_kind' => Boundary::LOCK_KIND,
            'consumer_service' => 'la-cortine.future-atomic-successor-creation',
            'permitted_transition' => Authority::PERMITTED_TRANSITION,
            'consumption_and_creation_atomic' => true,
            'authority_consumed' => false,
            'successor_created' => false,
            'partial_record_created' => false,
            'effect_started' => false,
            'continuing_authority' => false,
            'status' => Boundary::STATUS,
            'sealed' => true,
        ]);
    }

    private function seal(array $record): array
    {
        unset($record['record_digest']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }

    private function document(string $path): string
    {
        $contents = file_get_contents(dirname(__DIR__, 3).'/'.$path);
        self::assertNotFalse($contents);

        return $contents;
    }
}
