<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorProductionAdoptionAdversarialAuditResultContract as Result;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorProductionAdoptionAdversarialAuditService as Audit;

class ProviderBindingSuccessorProductionAdoptionBatch5AdversarialAuditTest
    extends ProviderBindingSuccessorProductionAdoptionBatch4Test
{
    public function testExactEligibleChainPassesPureReadOnlyAudit(): void
    {
        [$result] = $this->auditBasis();

        self::assertSame(Result::REQUIRED_FIELDS, array_keys($result));
        self::assertSame('PASSED', $result['classification']);
        self::assertTrue($result['read_only']);
        foreach (array_slice(Result::REQUIRED_FIELDS, 6) as $field) {
            self::assertFalse($result[$field], $field);
        }
    }

    public function testEveryRequiredProofIsMandatoryAndTrue(): void
    {
        [, $basis] = $this->auditBasis();
        foreach (array_keys($this->proofs()) as $proof) {
            $proofs = $this->proofs();
            $proofs[$proof] = false;
            self::assertSame('CONFLICTED', $this->audit($basis, $proofs)['classification'], $proof);
        }

        $missing = $this->proofs();
        unset($missing['defective_v1_refusal_proved']);
        self::assertSame('CONFLICTED', $this->audit($basis, $missing)['classification']);
    }

    public function testExpiryAndRevocationRefuseBeforeAdversarialValidation(): void
    {
        [, $basis] = $this->auditBasis();

        $expired = $basis;
        $expired['decision']['validity']['expires_at'] = '2026-08-31T00:30:00+00:00';
        $expired['decision'] = $this->seal($expired['decision']);
        self::assertSame('REFUSED', $this->audit($expired)['classification']);

        $revoked = $basis;
        $revoked['authority']['validity']['revocation_reference'] = [
            'id' => 'revocation.1',
            'digest' => str_repeat('d', 64),
            'schema' => 'imperium.revocation/v1',
        ];
        $revoked['authority'] = $this->seal($revoked['authority']);
        self::assertSame('REFUSED', $this->audit($revoked)['classification']);
    }

    public function testV1LineageSecretAndFalseLiveAdoptionAttacksConflict(): void
    {
        [, $basis] = $this->auditBasis();

        $legacy = $basis;
        $legacy['decision']['schema'] =
            'imperium.imperator.provider-binding-successor-production-decision/v1';
        $legacy['decision'] = $this->seal($legacy['decision']);
        self::assertSame('CONFLICTED', $this->audit($legacy)['classification']);

        $secret = $basis;
        $secret['authority']['credential_reference'] = 'env://forbidden';
        $secret['authority'] = $this->seal($secret['authority']);
        self::assertSame('CONFLICTED', $this->audit($secret)['classification']);

        $live = $basis;
        $live['adoption']['required_admission_contract']['status'] = 'IMPLEMENTED';
        $live['adoption']['live_adoption_performed'] = true;
        $live['adoption'] = $this->seal($live['adoption']);
        self::assertSame('CONFLICTED', $this->audit($live)['classification']);
    }

    public function testReconstructionPromotionAuthorityAndEffectAttacksConflict(): void
    {
        [, $basis] = $this->auditBasis();

        foreach ([
            'artifact_promoted',
            'successor_creation_authority_issued',
            'successor_creation_authority_consumed',
            'successor_created',
            'adoption_decided',
            'live_adoption_performed',
            'execution_admission_changed',
            'provider_binding_activated',
            'credential_or_capability_handled',
            'provider_invoked',
            'external_io_started',
            'provider_effect_started',
            'retry_authority_created',
            'continuing_authority',
        ] as $field) {
            $attacked = $basis;
            $attacked['reconstruction'][$field] = true;
            self::assertSame('CONFLICTED', $this->audit($attacked)['classification'], $field);
        }

        $digest = $basis;
        $digest['reconstruction']['proof_digest'] = str_repeat('0', 64);
        self::assertSame('CONFLICTED', $this->audit($digest)['classification']);
    }

    public function testAuditSourceHasNoPersistenceProductionOrEffectDependency(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3).'/src/Imperium/Runtime/Imperator/'
                .'ProviderBindingSuccessorProductionAdoptionAdversarialAuditService.php',
        );

        foreach ([
            'ImmutableRecordStore',
            'AtomicTransition',
            'AuthorityConsumptionStore',
            'CredentialBroker',
            'AgentMailEmailTransport',
            'ProviderBindingSuccessorProductionAdoptionFixtureStore',
            'ProviderBindingSuccessorProductionAdoptionAggregateReconstructor',
            'public function produce',
            'public function issue',
            'public function consume',
            'public function activate',
            'public function adopt',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testDocumentationAuthorizesTerminalAuditOnly(): void
    {
        $doc = $this->document(
            'docs/provider-binding-successor-production-adoption-batch-5-adversarial-audit.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-production-adoption-batch-5-complete.md',
        );

        foreach ([
            'BATCH_5_ADVERSARIAL_READINESS_AUDIT_PASSED',
            'immutable integrity',
            'acyclic v2 lineage',
            'defective v1 refusal',
            'expiry and revocation',
            'same-root contention',
            'recursive secret exclusion',
            'read-only reconstruction',
            'writes no record',
            'The provider binding remains BOUND_INACTIVE.',
        ] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }

        foreach ([
            'Only Provider Binding Successor Production Adoption Batch 6 terminal audit may next be considered.',
            'campaign result ledger',
            'may not activate a principal or provider binding',
            'may not issue or consume authority',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'may not migrate a live command',
            'may not open Iron Gate or Lazaretto',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function auditBasis(): array
    {
        $fixture = $this->productionFixture();
        $root = $this->temporaryRootForBatch4();
        try {
            $this->storeProductionChain($root, $fixture);
            $reconstruction = $this->reconstruct($root, $fixture);
        } finally {
            $this->removeTree($root);
        }

        $basis = [
            ...$fixture,
            'reconstruction' => $reconstruction,
            'at' => new \DateTimeImmutable('2026-08-31T01:00:00+00:00'),
        ];

        return [$this->audit($basis), $basis];
    }

    private function audit(array $basis, ?array $proofs = null): array
    {
        return (new Audit())->audit(
            $basis['reconstruction'],
            $basis['decision'],
            $basis['authority'],
            $basis['adoption'],
            $basis['decisionAuthority'],
            $basis['target'],
            $basis['input'],
            $basis['successor'],
            $basis['principal'],
            $basis['binding'],
            $basis['assurance'],
            $basis['boundary'],
            $proofs ?? $this->proofs(),
            $basis['at'],
        );
    }

    private function proofs(): array
    {
        return [
            'immutable_integrity_proved' => true,
            'acyclic_v2_lineage_proved' => true,
            'defective_v1_refusal_proved' => true,
            'lifecycle_eligibility_proved' => true,
            'expiry_refusal_proved' => true,
            'revocation_refusal_proved' => true,
            'substitution_refusal_proved' => true,
            'secret_exclusion_proved' => true,
            'before_commit_absence_proved' => true,
            'after_commit_winner_proved' => true,
            'exact_replay_converged' => true,
            'changed_evidence_conflicted' => true,
            'same_root_contention_proved' => true,
            'reconstruction_read_only_proved' => true,
            'v3_admission_not_implemented_proved' => true,
            'non_authority_perimeter_proved' => true,
        ];
    }
}
