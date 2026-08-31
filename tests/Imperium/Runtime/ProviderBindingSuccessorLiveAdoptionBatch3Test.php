<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorLiveAdoptionAuthorityContract as Authority;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorLiveAdoptionAtomicInertSeam;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorLiveAdoptionAtomicWinnerBoundaryContract as Boundary;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorLiveAdoptionAtomicWinnerBoundaryValidator as Validator;
use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorLiveAdoptionBatch3Test extends TestCase
{
    public function testExactSameRootBoundaryClassifiesReadyAndInert(): void
    {
        $boundary = $this->fixture();
        (new Validator())->assert($boundary);
        $result = (new ProviderBindingSuccessorLiveAdoptionAtomicInertSeam())
            ->inspect($boundary);

        self::assertSame(
            'READY_INERT_LIVE_ADOPTION_ATOMIC_BOUNDARY',
            $result['classification'],
        );
        self::assertTrue(
            $result['admission_consumption_adoption_and_binding_atomic'],
        );
        foreach ([
            'authority_consumed', 'execution_admitted', 'successor_adopted',
            'binding_transitioned', 'partial_record_created', 'effect_started',
            'continuing_authority',
        ] as $field) {
            self::assertFalse($result[$field], $field);
        }
    }

    public function testClaimedTransitionRefuses(): void
    {
        $boundary = $this->fixture();
        $boundary['execution_admitted'] = true;
        $boundary = $this->seal($boundary);

        $this->expectExceptionMessage(
            'PBL300_LIVE_ADOPTION_ATOMIC_WINNER_BOUNDARY_INVALID',
        );
        (new Validator())->assert($boundary);
    }

    public function testSecretBearingBoundaryRefuses(): void
    {
        $boundary = $this->fixture();
        $boundary['successor_binding_target']['credential_reference'] =
            'env://forbidden';
        $boundary = $this->seal($boundary);

        $this->expectExceptionMessage(
            'PBL300_LIVE_ADOPTION_ATOMIC_WINNER_BOUNDARY_INVALID',
        );
        (new Validator())->assert($boundary);
    }

    public function testInertSeamHasNoPersistenceDependency(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3)
                .'/src/Imperium/Runtime/LaCortine/'
                .'ProviderBindingSuccessorLiveAdoptionAtomicInertSeam.php',
        );

        foreach ([
            'AtomicTransition', 'AuthorityConsumptionStore',
            'ImmutableRecordStore', '->put(', '->consume(',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testContractGrantsNoAuthority(): void
    {
        foreach (Boundary::NON_AUTHORITIES as $name => $value) {
            self::assertFalse($value, $name);
        }
        foreach ([
            'authority_consumed', 'execution_admitted', 'successor_adopted',
            'binding_transitioned', 'effect_started',
        ] as $field) {
            self::assertFalse(Boundary::INVARIANTS[$field], $field);
        }
    }

    public function testDocumentationAuthorizesProofOnly(): void
    {
        $doc = $this->document(
            'docs/provider-binding-successor-live-adoption-batch-3-inert-atomic-seam.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-live-adoption-batch-3-complete.md',
        );

        foreach ([
            'BATCH_3_INERT_SAME_ROOT_V3_ADMISSION_CONSUMPTION_ADOPTION_AND_BINDING_BOUNDARY_COMPLETE',
            'The required lock kind is exact_replay_contention_root.',
            'V3 admission, authority consumption, successor adoption and binding transition must be one atomic commit.',
            'A crash before commit must leave no consumption, admission, adoption or binding transition.',
            'No partial record may exist.',
        ] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }

        foreach ([
            'Only Provider Binding Successor Live Adoption Batch 4 caller-supplied interruption, replay, contention, expiry and revocation proof may next be considered.',
            'may define disposable fixture proof and read-only assertions only',
            'may not produce a decision, issue or consume live authority, admit live execution, adopt a live successor or change live binding state',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'may not start a provider effect',
            'may not authorize retry',
            'may not migrate a live command',
            'may not open Iron Gate or Lazaretto',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function fixture(): array
    {
        $reference = fn (string $id, string $digest, string $schema): array => [
            'id' => $id, 'digest' => $digest, 'schema' => $schema,
        ];

        return $this->seal([
            'schema' => Boundary::SCHEMA,
            'winner_boundary_id' => 'live-adoption-atomic-winner.1',
            'instance_id' => 'instance.1',
            'adoption_decision' => $reference(
                'live-adoption-decision.1', str_repeat('a', 64),
                'imperium.imperator.provider-binding-successor-execution-adoption-decision-boundary/v1',
            ),
            'authority_schema' => Authority::SCHEMA,
            'authority_source' => $reference(
                'live-adoption-authority.1', str_repeat('b', 64), Authority::SCHEMA,
            ),
            'custody_source' => $reference(
                'live-adoption-authority-custody.1', str_repeat('c', 64),
                'imperium.clavium.provider-binding-successor-live-adoption-authority-durable-custody-boundary/v1',
            ),
            'completed_successor' => $reference(
                'binding-successor.1', str_repeat('d', 64),
                'imperium.la-cortine.provider-binding-activation-reconciled-lifecycle-successor/v1',
            ),
            'atomic_creation_winner' => $reference(
                'successor-creation-winner.1', str_repeat('e', 64),
                'imperium.la-cortine.provider-binding-successor-atomic-creation-winner-boundary/v1',
            ),
            'adoption_target' => $reference(
                'successor-adoption-target.1', str_repeat('1', 64),
                'imperium.la-cortine.provider-binding-successor-execution-adoption-target/v1',
            ),
            'v3_admission' => $reference(
                'successor-admission-v3.1', str_repeat('2', 64),
                'imperium.la-cortine.governed-provider-execution-admission/v3',
            ),
            'adoption_join' => $reference(
                'successor-to-v3-join.1', str_repeat('3', 64),
                'imperium.la-cortine.provider-binding-successor-to-v3-adoption-join-boundary/v1',
            ),
            'original_binding' => $reference(
                'binding.1', str_repeat('4', 64),
                'imperium.la-cortine.provider-implementation-binding/v1',
            ),
            'successor_binding_target' => $reference(
                'binding-successor-target.1', str_repeat('5', 64),
                'imperium.la-cortine.provider-binding-successor-execution-adoption-target/v1',
            ),
            'replay_contention_root' => 'binding-reconciliation-root.1',
            'lock_kind' => Boundary::LOCK_KIND,
            'consumer_service' =>
                'la-cortine.future-atomic-successor-live-adoption',
            'permitted_transition' => Authority::PERMITTED_TRANSITION,
            'admission_consumption_adoption_and_binding_atomic' => true,
            'authority_consumed' => false,
            'execution_admitted' => false,
            'successor_adopted' => false,
            'binding_transitioned' => false,
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
        return (string) preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(dirname(__DIR__, 3).'/'.$path),
        );
    }
}
