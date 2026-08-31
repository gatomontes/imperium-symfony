<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorLiveAdoptionAuthorityContract as Authority;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorLiveAdoptionAtomicWinnerBoundaryContract as Boundary;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorLiveAdoptionInterruptionProofService as Proof;
use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorLiveAdoptionBatch4Test extends TestCase
{
    public function testBeforeCommitLeavesNoWinnerOrPartialState(): void
    {
        $result = (new Proof())->prove(
            $this->fixture(),
            $this->lifecycle(),
            $this->at(),
            Proof::CUT_BEFORE_COMMIT,
        );

        self::assertSame('INTERRUPTED_BEFORE_COMMIT_NO_WINNER', $result['classification']);
        self::assertSame(0, $result['winner_count']);
        foreach (array_slice($result, 4) as $field => $value) {
            self::assertFalse($value, $field);
        }
    }

    public function testAfterCommitHasOneWinnerAndExactReplayConverges(): void
    {
        $proof = new Proof();
        $boundary = $this->fixture();
        $result = $proof->prove(
            $boundary,
            $this->lifecycle(),
            $this->at(),
            Proof::CUT_AFTER_COMMIT,
        );

        self::assertSame('INTERRUPTED_AFTER_COMMIT_ONE_WINNER', $result['classification']);
        self::assertSame(1, $result['winner_count']);
        self::assertTrue($result['immutable_commit_observed']);
        self::assertTrue($result['authority_consumed']);
        self::assertTrue($result['execution_admitted']);
        self::assertTrue($result['successor_adopted']);
        self::assertTrue($result['binding_transitioned']);
        self::assertFalse($result['partial_record_created']);
        self::assertFalse($result['effect_started']);
        self::assertSame($result, $proof->replay($result, $boundary));
    }

    public function testChangedEvidenceUnderTheSameRootConflicts(): void
    {
        $proof = new Proof();
        $boundary = $this->fixture();
        $winner = $proof->prove(
            $boundary,
            $this->lifecycle(),
            $this->at(),
            Proof::CUT_AFTER_COMMIT,
        );
        $boundary['successor_binding_target']['id'] = 'binding-successor-target.2';
        $boundary = $this->seal($boundary);

        $this->expectExceptionMessage(
            'PBL410_LIVE_ADOPTION_SAME_ROOT_CONTENTION_CONFLICT',
        );
        $proof->replay($winner, $boundary);
    }

    public function testExpiryAndRevocationRefuseBeforeProof(): void
    {
        $expired = $this->lifecycle();
        $expired['expires_at'] = '2026-08-31T19:30:00+00:00';
        $this->expectFailure(
            fn () => (new Proof())->prove(
                $this->fixture(), $expired, $this->at(), Proof::CUT_AFTER_COMMIT,
            ),
        );

        $revoked = $this->lifecycle();
        $revoked['revocation_reference'] = [
            'id' => 'revocation.1',
            'digest' => str_repeat('f', 64),
            'schema' => 'imperium.revocation/v1',
        ];
        $this->expectFailure(
            fn () => (new Proof())->prove(
                $this->fixture(), $revoked, $this->at(), Proof::CUT_AFTER_COMMIT,
            ),
        );
    }

    public function testProofHasNoPersistenceOrEffectDependency(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3)
                .'/src/Imperium/Runtime/LaCortine/'
                .'ProviderBindingSuccessorLiveAdoptionInterruptionProofService.php',
        );

        foreach ([
            'AtomicTransition', 'AuthorityConsumptionStore',
            'ImmutableRecordStore', 'CredentialBroker', 'ProviderTransport',
            '->put(', '->consume(',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testDocumentationAuthorizesReconstructionOnly(): void
    {
        $doc = $this->document(
            'docs/provider-binding-successor-live-adoption-batch-4-proof.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-live-adoption-batch-4-complete.md',
        );

        foreach ([
            'BATCH_4_DISPOSABLE_INTERRUPTION_REPLAY_CONTENTION_EXPIRY_AND_REVOCATION_PROOF_COMPLETE',
            'Interruption before immutable commit leaves no winner.',
            'Interruption after immutable commit exposes one deterministic proof winner.',
            'Exact replay converges on that winner.',
            'Changed evidence under the same root conflicts.',
            'Expiry and revocation refuse before proof evaluation.',
            'No interruption path creates a partial record.',
        ] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }

        foreach ([
            'Only Provider Binding Successor Live Adoption Batch 5 read-only aggregate reconstruction may next be considered.',
            'may define a read-only aggregate and pure reconstructor only',
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

    private function expectFailure(callable $callable): void
    {
        try {
            $callable();
            self::fail('Expected lifecycle refusal.');
        } catch (\RuntimeException $error) {
            self::assertSame(
                'PBL420_LIVE_ADOPTION_AUTHORITY_EXPIRED_OR_REVOKED',
                $error->getMessage(),
            );
        }
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
