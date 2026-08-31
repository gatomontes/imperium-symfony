<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\PrincipalActivationDecisionAuthorityProvenanceAdversarialAuditResultContract as Result;
use App\Imperium\Runtime\Imperator\PrincipalActivationDecisionAuthorityProvenanceAdversarialAuditService as Audit;

final class PrincipalActivationDecisionAuthorityProvenanceRemediationBatch6AdversarialAuditTest
    extends PrincipalActivationDecisionAuthorityProvenanceRemediationBatch5CProductionTest
{
    public function testExactProductionPassesReadOnlyAudit(): void
    {
        [$result] = $this->auditBasis();

        self::assertSame(Result::REQUIRED_FIELDS, array_keys($result));
        self::assertSame('PASSED', $result['classification']);
        self::assertTrue($result['read_only']);
        foreach ([
            'record_created',
            'record_repaired',
            'authority_issued',
            'authority_consumed',
            'principal_activated',
            'binding_activated',
            'credential_or_capability_handled',
            'provider_invoked',
            'external_action_performed',
        ] as $field) {
            self::assertFalse($result[$field], $field);
        }
    }

    public function testEligibilityIdentityScopeLifecycleAndAuthorizationAttacksConflict(): void
    {
        [, $basis, $production] = $this->auditBasis();

        $cases = [];

        $eligibility = $production;
        $eligibility['eligible_aggregate']['classification'] = 'REFUSED';
        $cases[] = self::seal($eligibility);

        $identity = $production;
        $identity['pending_successor_principal']['identity']['operator_id'] = 'attacker';
        $identity['pending_successor_principal'] =
            self::seal($identity['pending_successor_principal']);
        $cases[] = self::seal($identity);

        $scope = $production;
        $scope['pending_successor_principal']['authority_scope']['provider_execution_authority'] = true;
        $scope['pending_successor_principal'] =
            self::seal($scope['pending_successor_principal']);
        $cases[] = self::seal($scope);

        $lifecycle = $production;
        $lifecycle['applied_lifecycle_disposition']['id'] = 'other-disposition';
        $cases[] = self::seal($lifecycle);

        $authorization = $production;
        $authorization['consumed_issuance_authorization']['source_authorization']['id'] =
            'other-authorization';
        $cases[] = self::seal($authorization);

        foreach ($cases as $attacked) {
            $result = $this->audit($attacked, $basis, $basis['at']);
            self::assertSame('CONFLICTED', $result['classification']);
            self::assertTrue($result['read_only']);
            self::assertFalse($result['record_repaired']);
        }
    }

    public function testReplayContentionAndInterruptionProofAttacksConflict(): void
    {
        [, $basis, $production] = $this->auditBasis();

        foreach (array_keys($this->proofs()) as $proof) {
            $proofs = $this->proofs();
            $proofs[$proof] = false;
            $result = $this->audit($production, $basis, $basis['at'], $proofs);
            self::assertSame('CONFLICTED', $result['classification'], $proof);
        }

        $missing = $this->proofs();
        unset($missing['after_commit_reconstructed_exact_winner']);
        self::assertSame(
            'CONFLICTED',
            $this->audit($production, $basis, $basis['at'], $missing)['classification'],
        );
    }

    public function testExpiryAndRevocationRefuse(): void
    {
        [, $basis, $production] = $this->auditBasis();

        self::assertSame(
            'REFUSED',
            $this->audit(
                $production,
                $basis,
                $basis['at']->modify('+10 minutes'),
            )['classification'],
        );

        $revoked = $basis;
        $revoked['authorization']['revocation'] = self::referenceRecord('audit-revocation');
        $revoked['authorization'] = self::seal($revoked['authorization']);
        self::assertSame(
            'REFUSED',
            $this->audit($production, $revoked, $basis['at'])['classification'],
        );
    }

    public function testSecretAndNonAuthorityPerimeterAttacksConflict(): void
    {
        [, $basis, $production] = $this->auditBasis();

        $secret = $production;
        $secret['activation_decision']['limitations'] = [
            'api_key' => 'must-never-persist',
        ];
        $secret['activation_decision'] = self::seal($secret['activation_decision']);
        $secret = self::seal($secret);
        self::assertSame(
            'CONFLICTED',
            $this->audit($secret, $basis, $basis['at'])['classification'],
        );

        foreach ([
            'provider_executor_principal_activated',
            'provider_binding_activated',
            'activation_authority_consumed',
            'credential_or_capability_handled',
            'provider_invoked',
            'external_action_performed',
            'continuing_authority',
        ] as $field) {
            $attacked = $production;
            $attacked[$field] = true;
            $attacked = self::seal($attacked);
            self::assertSame(
                'CONFLICTED',
                $this->audit($attacked, $basis, $basis['at'])['classification'],
                $field,
            );
        }
    }

    public function testAuditSourceHasNoPersistenceProductionOrEffectDependency(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3).'/src/Imperium/Runtime/Imperator/'
                .'PrincipalActivationDecisionAuthorityProvenanceAdversarialAuditService.php',
        );

        foreach ([
            'ImmutableRecordStore',
            'AtomicTransition',
            'AuthorityConsumptionStore',
            'CredentialBroker',
            'AgentMailEmailTransport',
            'PrincipalActivationDecisionAuthorityProvenanceProductionService',
            'public function produce',
            'public function issue',
            'public function consume',
            'public function activate',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testDocumentationAuthorizesTerminalAuditOnly(): void
    {
        $doc = $this->document('docs/principal-activation-decision-authority-provenance-remediation-batch-6-adversarial-audit.md');
        $handoff = $this->document('docs/handoffs/principal-activation-decision-authority-provenance-remediation-batch-6-complete.md');

        foreach ([
            'BATCH_6_ADVERSARIAL_AUDIT_PASSED',
            'aggregate eligibility',
            'exact successor identity',
            'separate lifecycle activation',
            'changed-evidence contention',
            'before/after-commit recovery',
            'recursively refuses credential secrets',
            'writes no record',
        ] as $claim) {
            self::assertNotFalse(stripos($doc, $claim), $claim);
        }
        foreach ([
            'Only remediation Batch 7 terminal audit may next be considered',
            'campaign result ledger',
            'supersession of the original Batch 2 refusal basis',
            'may not create or repair a production record',
            'Provider Effect Principal and Binding Activation remains paused',
            'Iron Gate and Lazaretto remain closed',
            'one terminal-audit batch',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }

    private function auditBasis(): array
    {
        $basis = $this->productionBasis();
        $production = $this->produce($basis);
        $result = $this->audit($production, $basis, $basis['at']);

        return [$result, $basis, $production];
    }

    private function audit(
        array $production,
        array $basis,
        \DateTimeImmutable $at,
        ?array $proofs = null,
    ): array {
        return (new Audit())->audit(
            $production,
            $basis['source'],
            $basis['transition'],
            $basis['activation'],
            $basis['envelope'],
            $basis['authorization'],
            $proofs ?? $this->proofs(),
            $at,
        );
    }

    private function proofs(): array
    {
        return [
            'exact_replay_converged' => true,
            'changed_evidence_conflicted' => true,
            'before_commit_left_no_winner' => true,
            'after_commit_reconstructed_exact_winner' => true,
            'single_combined_winner' => true,
        ];
    }
}
