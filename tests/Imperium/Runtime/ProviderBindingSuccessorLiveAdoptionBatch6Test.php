<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorLiveAdoptionAdversarialAuditResultContract as Result;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorLiveAdoptionAdversarialAuditService as Audit;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorLiveAdoptionAuthorityContract as Authority;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorLiveAdoptionAggregateReconstructor as Reconstructor;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorLiveAdoptionAtomicWinnerBoundaryContract as Boundary;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorLiveAdoptionInterruptionProofService as Proof;
use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorLiveAdoptionBatch6Test extends TestCase
{
    public function testExactCallerSuppliedWinnerPassesReadOnlyAudit(): void
    {
        $result = $this->audit($this->fixture());

        self::assertSame('PASSED', $result['classification']);
        self::assertSame(Result::SCHEMA, $result['schema']);
        self::assertSame(Result::REQUIRED_FIELDS, array_keys($result));
        self::assertTrue($result['read_only']);
        foreach (array_slice($result, 6) as $field => $value) {
            self::assertFalse($value, $field);
        }
    }

    public function testEveryAdversarialProofIsRequiredAndTrue(): void
    {
        $fixture = $this->fixture();
        $proofs = $this->proofs();
        array_pop($proofs);
        self::assertSame(
            'CONFLICTED',
            $this->audit($fixture, $proofs)['classification'],
        );

        $proofs = $this->proofs();
        $proofs['same_root_contention_proved'] = false;
        self::assertSame(
            'CONFLICTED',
            $this->audit($fixture, $proofs)['classification'],
        );
    }

    public function testExpiryAndRevocationRefuseBeforeAudit(): void
    {
        $expired = $this->fixture();
        $expired['lifecycle']['expires_at'] = '2026-08-31T19:30:00+00:00';
        self::assertSame('REFUSED', $this->audit($expired)['classification']);

        $revoked = $this->fixture();
        $revoked['lifecycle']['revocation_reference'] = [
            'id' => 'revocation.1',
            'digest' => str_repeat('f', 64),
            'schema' => 'imperium.imperator.revocation/v1',
        ];
        self::assertSame('REFUSED', $this->audit($revoked)['classification']);
    }

    public function testSubstitutionSecretPartialAndEffectAttacksConflict(): void
    {
        $changed = $this->fixture();
        $changed['boundary']['successor_binding_target']['id'] =
            'binding-successor-target.2';
        $changed['boundary'] = $this->seal($changed['boundary']);
        self::assertSame('CONFLICTED', $this->audit($changed)['classification']);

        $secret = $this->fixture();
        $secret['boundary']['credential_reference'] = 'env://forbidden';
        $secret['boundary'] = $this->seal($secret['boundary']);
        self::assertSame('CONFLICTED', $this->audit($secret)['classification']);

        $partial = $this->fixture();
        $partial['boundary']['partial_record_created'] = true;
        $partial['boundary'] = $this->seal($partial['boundary']);
        self::assertSame('CONFLICTED', $this->audit($partial)['classification']);

        $effect = $this->fixture();
        $effect['aggregate']['provider_effect_started'] = true;
        self::assertSame('CONFLICTED', $this->audit($effect)['classification']);
    }

    public function testAuditSourceHasNoPersistenceOrEffectDependency(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 3)
                .'/src/Imperium/Runtime/Imperator/'
                .'ProviderBindingSuccessorLiveAdoptionAdversarialAuditService.php',
        );
        self::assertNotFalse($source);

        foreach ([
            'AtomicTransition', 'AuthorityConsumptionStore',
            'ImmutableRecordStore', 'MutableStateStore', 'FixtureStore',
            'CredentialBroker', 'ProviderTransport',
            'public function produce', 'public function issue',
            'public function consume', 'public function adopt',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testDocumentationAuthorizesTerminalAuditOnly(): void
    {
        $doc = $this->document(
            'docs/provider-binding-successor-live-adoption-batch-6-adversarial-audit.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-live-adoption-batch-6-complete.md',
        );

        foreach ([
            'BATCH_6_READ_ONLY_LIVE_ADOPTION_ADVERSARIAL_READINESS_AUDIT_PASSED',
            'Interruption before commit leaves no winner.',
            'Exact replay converges.',
            'Changed evidence and same-root contenders conflict.',
            'Expired or revoked lineage refuses.',
            'Partial-state claims conflict.',
            'False v3, live-transition and provider-effect claims conflict.',
            'v3 execution admission remains NOT_IMPLEMENTED',
        ] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }

        foreach ([
            'Only Provider Binding Successor Live Adoption Batch 7 terminal audit and campaign closure may next be considered.',
            'may write terminal documentation and focused audit guards only',
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

    private function audit(array $fixture, ?array $proofs = null): array
    {
        return (new Audit())->audit(
            $fixture['boundary'],
            $fixture['winnerProof'],
            $fixture['aggregate'],
            $fixture['lifecycle'],
            $proofs ?? $this->proofs(),
            $this->at(),
        );
    }

    private function proofs(): array
    {
        return array_fill_keys(Audit::REQUIRED_PROOFS, true);
    }

    private function fixture(): array
    {
        $boundary = $this->boundary();
        $winnerProof = (new Proof())->prove(
            $boundary, $this->lifecycle(), $this->at(), Proof::CUT_AFTER_COMMIT,
        );

        return [
            'boundary' => $boundary,
            'winnerProof' => $winnerProof,
            'aggregate' => (new Reconstructor())->reconstruct($boundary, $winnerProof),
            'lifecycle' => $this->lifecycle(),
        ];
    }

    private function boundary(): array
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
