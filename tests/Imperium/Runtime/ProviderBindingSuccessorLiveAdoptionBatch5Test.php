<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorLiveAdoptionAuthorityContract as Authority;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorLiveAdoptionAggregateReconstructor as Reconstructor;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorLiveAdoptionAtomicWinnerBoundaryContract as Boundary;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorLiveAdoptionInterruptionProofService as Proof;
use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorLiveAdoptionBatch5Test extends TestCase
{
    public function testAbsentAndPartialEvidenceRemainNonWinning(): void
    {
        $reconstructor = new Reconstructor();

        self::assertSame('ABSENT', $reconstructor->reconstruct(null, null)['classification']);
        self::assertSame(
            'INCOMPLETE',
            $reconstructor->reconstruct($this->fixture(), null)['classification'],
        );

        $before = (new Proof())->prove(
            $this->fixture(), $this->lifecycle(), $this->at(), Proof::CUT_BEFORE_COMMIT,
        );
        self::assertSame(
            'INCOMPLETE',
            $reconstructor->reconstruct($this->fixture(), $before)['classification'],
        );
    }

    public function testExactWinnerReconstructsDeterministicallyAndReadOnly(): void
    {
        $boundary = $this->fixture();
        $proof = (new Proof())->prove(
            $boundary, $this->lifecycle(), $this->at(), Proof::CUT_AFTER_COMMIT,
        );
        $reconstructor = new Reconstructor();

        $first = $reconstructor->reconstruct($boundary, $proof);
        $second = $reconstructor->reconstruct($boundary, $proof);

        self::assertSame('EXACT_LIVE_ADOPTION_WINNER', $first['classification']);
        self::assertSame([], $first['reasons']);
        self::assertSame($first, $second);
        self::assertSame($boundary['record_digest'], $first['chain']['winner_boundary']['digest']);
        self::assertTrue($first['read_only']);
        foreach ($first as $name => $value) {
            if (str_ends_with($name, '_created')
                || str_ends_with($name, '_repaired')
                || str_ends_with($name, '_replaced')
                || str_ends_with($name, '_issued')
                || str_ends_with($name, '_consumed')
                || str_ends_with($name, '_admitted')
                || str_ends_with($name, '_adopted')
                || str_ends_with($name, '_transitioned')
                || str_ends_with($name, '_handled')
                || str_ends_with($name, '_invoked')
                || str_ends_with($name, '_started')) {
                self::assertFalse($value, $name);
            }
        }
        self::assertFalse($first['continuing_authority']);
    }

    public function testChangedEvidenceAndTamperedProofClassifyConflicted(): void
    {
        $boundary = $this->fixture();
        $proof = (new Proof())->prove(
            $boundary, $this->lifecycle(), $this->at(), Proof::CUT_AFTER_COMMIT,
        );

        $changed = $boundary;
        $changed['successor_binding_target']['id'] = 'binding-successor-target.2';
        $changed = $this->seal($changed);
        $conflict = (new Reconstructor())->reconstruct($changed, $proof);
        self::assertSame('CONFLICTED', $conflict['classification']);
        self::assertContains(
            'PBL410_LIVE_ADOPTION_SAME_ROOT_CONTENTION_CONFLICT',
            $conflict['reasons'],
        );

        $proof['successor_adopted'] = false;
        $tampered = (new Reconstructor())->reconstruct($boundary, $proof);
        self::assertSame('CONFLICTED', $tampered['classification']);
        self::assertContains('PBL411_LIVE_ADOPTION_PROOF_TAMPERED', $tampered['reasons']);
    }

    public function testInvalidBoundaryClassifiesRefusedWithoutReplacement(): void
    {
        $boundary = $this->fixture();
        $proof = (new Proof())->prove(
            $boundary, $this->lifecycle(), $this->at(), Proof::CUT_AFTER_COMMIT,
        );
        $boundary['status'] = 'IMPLEMENTED';
        $boundary = $this->seal($boundary);

        $result = (new Reconstructor())->reconstruct($boundary, $proof);

        self::assertSame('REFUSED', $result['classification']);
        self::assertNotSame([], $result['reasons']);
        self::assertFalse($result['evidence_replaced']);
        self::assertFalse($result['authority_consumed']);
        self::assertFalse($result['binding_transitioned']);
    }

    public function testReconstructorHasNoPersistenceOrEffectDependency(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3)
                .'/src/Imperium/Runtime/LaCortine/'
                .'ProviderBindingSuccessorLiveAdoptionAggregateReconstructor.php',
        );

        foreach ([
            'AtomicTransition', 'ImmutableRecordStore', 'MutableStateStore',
            'AuthorityConsumptionStore', 'CredentialBroker', 'ProviderTransport',
            '->put(', '->consume(',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testDocumentationAuthorizesReadOnlyAuditNextOnly(): void
    {
        $doc = $this->document(
            'docs/provider-binding-successor-live-adoption-batch-5-reconstruction.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-live-adoption-batch-5-complete.md',
        );

        foreach ([
            'BATCH_5_READ_ONLY_LIVE_ADOPTION_AGGREGATE_RECONSTRUCTION_COMPLETE',
            'ABSENT', 'INCOMPLETE', 'CONFLICTED', 'REFUSED',
            'EXACT_LIVE_ADOPTION_WINNER',
            'creates, repairs and replaces no evidence',
            'performs no live transition',
        ] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }

        foreach ([
            'Only Provider Binding Successor Live Adoption Batch 6 read-only adversarial readiness audit may next be considered.',
            'may define pure caller-supplied audit guards only',
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
        $ref = fn (string $id, string $digit, string $schema): array => [
            'id' => $id, 'digest' => str_repeat($digit, 64), 'schema' => $schema,
        ];

        return $this->seal([
            'schema' => Boundary::SCHEMA,
            'winner_boundary_id' => 'live-adoption-atomic-winner.1',
            'instance_id' => 'instance.1',
            'adoption_decision' => $ref('live-adoption-decision.1', 'a', 'imperium.imperator.provider-binding-successor-execution-adoption-decision-boundary/v1'),
            'authority_schema' => Authority::SCHEMA,
            'authority_source' => $ref('live-adoption-authority.1', 'b', Authority::SCHEMA),
            'custody_source' => $ref('live-adoption-authority-custody.1', 'c', 'imperium.clavium.provider-binding-successor-live-adoption-authority-durable-custody-boundary/v1'),
            'completed_successor' => $ref('binding-successor.1', 'd', 'imperium.la-cortine.provider-binding-activation-reconciled-lifecycle-successor/v1'),
            'atomic_creation_winner' => $ref('successor-creation-winner.1', 'e', 'imperium.la-cortine.provider-binding-successor-atomic-creation-winner-boundary/v1'),
            'adoption_target' => $ref('successor-adoption-target.1', '1', 'imperium.la-cortine.provider-binding-successor-execution-adoption-target/v1'),
            'v3_admission' => $ref('successor-admission-v3.1', '2', 'imperium.la-cortine.governed-provider-execution-admission/v3'),
            'adoption_join' => $ref('successor-to-v3-join.1', '3', 'imperium.la-cortine.provider-binding-successor-to-v3-adoption-join-boundary/v1'),
            'original_binding' => $ref('binding.1', '4', 'imperium.la-cortine.provider-implementation-binding/v1'),
            'successor_binding_target' => $ref('binding-successor-target.1', '5', 'imperium.la-cortine.provider-binding-successor-execution-adoption-target/v1'),
            'replay_contention_root' => 'binding-reconciliation-root.1',
            'lock_kind' => Boundary::LOCK_KIND,
            'consumer_service' => 'la-cortine.future-atomic-successor-live-adoption',
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

    private function lifecycle(): array
    {
        return [
            'effective_at' => '2026-08-31T19:00:00+00:00',
            'expires_at' => '2026-08-31T21:00:00+00:00',
            'revocation_reference' => null,
        ];
    }

    private function at(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-08-31T20:00:00+00:00');
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
