<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\ProviderBindingActivationStateReconciliationAdversarialAuditResultContract as Result;
use App\Imperium\Runtime\Imperator\ProviderBindingActivationStateReconciliationAdversarialAuditService as Audit;

final class ProviderBindingActivationStateReconciliationBatch5AdversarialAuditTest
    extends ProviderBindingActivationStateReconciliationBatch4Test
{
    public function testExactEligibleChainPassesReadOnlyAudit(): void
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
            $result = $this->audit($basis, $proofs);
            self::assertSame('CONFLICTED', $result['classification'], $proof);
        }

        $missing = $this->proofs();
        unset($missing['same_root_contention_proved']);
        self::assertSame('CONFLICTED', $this->audit($basis, $missing)['classification']);
    }

    public function testExpiryAndRevocationRefuseBeforeAdversarialValidation(): void
    {
        [, $basis] = $this->auditBasis();

        $expired = $basis;
        $expired['target']['validity']['expires_at'] = '2026-08-31T00:30:00+00:00';
        $expired['target'] = $this->seal($expired['target']);
        self::assertSame('REFUSED', $this->audit($expired)['classification']);

        $revoked = $basis;
        $revoked['input']['activation_authority']['revocation_reference'] = [
            'id' => 'revocation.1',
            'digest' => str_repeat('c', 64),
            'schema' => 'imperium.revocation/v1',
        ];
        $revoked['input'] = $this->seal($revoked['input']);
        self::assertSame('REFUSED', $this->audit($revoked)['classification']);
    }

    public function testLineageSecretAndNonAuthorityAttacksConflict(): void
    {
        [, $basis] = $this->auditBasis();

        $lineage = $basis;
        $lineage['successor']['operation_scope']['provider_id'] = 'attacker';
        $lineage['successor'] = $this->seal($lineage['successor']);
        self::assertSame('CONFLICTED', $this->audit($lineage)['classification']);

        $secret = $basis;
        $secret['successor']['credential_reference'] = 'env://forbidden';
        $secret['successor'] = $this->seal($secret['successor']);
        self::assertSame('CONFLICTED', $this->audit($secret)['classification']);

        foreach ([
            'original_binding_mutated',
            'global_bound_active_asserted',
            'credential_or_capability_handled',
            'provider_invoked',
            'external_io_started',
            'provider_effect_started',
            'retry_authority_created',
            'continuing_authority',
        ] as $field) {
            $attacked = $basis;
            $attacked['successor'][$field] = true;
            $attacked['successor'] = $this->seal($attacked['successor']);
            self::assertSame('CONFLICTED', $this->audit($attacked)['classification'], $field);
        }
    }

    public function testReconstructionPromotionOrDigestAttackConflicts(): void
    {
        [, $basis] = $this->auditBasis();

        $promoted = $basis;
        $promoted['reconstruction']['artifact_promoted'] = true;
        self::assertSame('CONFLICTED', $this->audit($promoted)['classification']);

        $digest = $basis;
        $digest['reconstruction']['proof_digest'] = str_repeat('0', 64);
        self::assertSame('CONFLICTED', $this->audit($digest)['classification']);
    }

    public function testAuditSourceHasNoPersistenceProductionOrEffectDependency(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3).'/src/Imperium/Runtime/Imperator/'
                .'ProviderBindingActivationStateReconciliationAdversarialAuditService.php',
        );

        foreach ([
            'ImmutableRecordStore',
            'AtomicTransition',
            'AuthorityConsumptionStore',
            'CredentialBroker',
            'AgentMailEmailTransport',
            'ProviderBindingActivationReconciliationFixtureStore',
            'ProviderBindingActivationReconciliationAggregateReconstructor',
            'public function produce',
            'public function issue',
            'public function consume',
            'public function activate',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testBatch5DocumentationAuthorizesTerminalAuditOnly(): void
    {
        $doc = $this->document(
            'docs/provider-binding-activation-state-reconciliation-batch-5-adversarial-audit.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-binding-activation-state-reconciliation-batch-5-complete.md',
        );

        foreach ([
            'BATCH_5_ADVERSARIAL_READINESS_AUDIT_PASSED',
            'immutable integrity',
            'exact lineage',
            'expiry and revocation',
            'same-root contention',
            'recursive secret exclusion',
            'read-only reconstruction',
            'writes no record',
            'provider binding remains BOUND_INACTIVE',
        ] as $finding) {
            self::assertNotFalse(stripos($doc, $finding), $finding);
        }

        foreach ([
            'Only Provider Binding Activation State Reconciliation Batch 6 terminal audit',
            'campaign result ledger',
            'may not activate a provider binding',
            'may not issue or consume authority',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'one terminal-audit batch',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }

    private function auditBasis(): array
    {
        $fixture = $this->fixture();
        $root = sys_get_temp_dir().'/imperium-pbr-batch5-'.bin2hex(random_bytes(8));
        mkdir($root, 0770, true);
        try {
            $this->storeChain($root, $fixture);
            $reconstruction = $this->reconstructor($root)->reconstruct(
                'binding-reconciliation-root.1',
                $fixture['principal'],
                $fixture['binding'],
                $fixture['assurance'],
                $fixture['boundary'],
                new \DateTimeImmutable('2026-08-31T01:00:00+00:00'),
            );
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
            'exact_lineage_proved' => true,
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
            'non_authority_perimeter_proved' => true,
        ];
    }
}
