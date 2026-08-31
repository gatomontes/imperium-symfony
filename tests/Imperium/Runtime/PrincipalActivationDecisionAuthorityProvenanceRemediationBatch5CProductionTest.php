<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\PrincipalActivationDecisionAuthorityProvenanceAggregateResultContract as Aggregate;
use App\Imperium\Runtime\Imperator\PrincipalActivationDecisionAuthorityProvenanceProductionInterruptionProofService as Proof;
use App\Imperium\Runtime\Imperator\PrincipalActivationDecisionAuthorityProvenanceProductionService as Production;

final class PrincipalActivationDecisionAuthorityProvenanceRemediationBatch5CProductionTest
    extends PrincipalActivationDecisionAuthorityProvenanceRemediationBatch5BTest
{
    public function testOneCombinedWinnerProducesExactDecisionAndUnconsumedAuthority(): void
    {
        $basis = $this->productionBasis();
        $result = $this->produce($basis);

        self::assertTrue($result['combined_winner']);
        self::assertSame('PENDING_ACTIVATION', $result['pending_successor_principal']['status']);
        self::assertSame('ACTIVE', $result['effective_principal_status']);
        self::assertTrue($result['consumed_issuance_authorization']['consumed']);
        self::assertFalse($result['consumed_issuance_authorization']['continuing_authority']);
        self::assertSame('AUTHORIZED', $result['activation_decision']['disposition']);
        self::assertFalse($result['activation_decision']['activation_authority']['consumed']);
        self::assertFalse($result['activation_authority_consumed']);
        self::assertFalse($result['provider_executor_principal_activated']);
        self::assertFalse($result['provider_binding_activated']);
        self::assertFalse($result['credential_or_capability_handled']);
        self::assertFalse($result['provider_invoked']);
        self::assertFalse($result['external_action_performed']);
    }

    public function testExactReplayConvergesAndChangedEvidenceConflicts(): void
    {
        $basis = $this->productionBasis();
        $service = new Production($this->root);
        $winner = $this->produce($basis, $service);

        self::assertSame($winner, $this->produce($basis, $service));

        $basis['envelope']['rationale'] = 'Changed but structurally valid rationale.';
        $basis['envelope'] = self::seal($basis['envelope']);

        $this->expectExceptionMessage('PST111_IMMUTABLE_RECORD_CONFLICT');
        $this->produce($basis, $service);
    }

    public function testBeforeAndAfterCombinedCommitCutsRecoverExactly(): void
    {
        $basis = $this->productionBasis();
        $proof = new Proof($this->root);
        $service = new Production($this->root);
        $productionId = $this->productionId($basis);

        try {
            $this->produceThroughProof($basis, $proof, Proof::CUT_BEFORE_COMBINED_COMMIT);
            self::fail('Before-commit cut did not interrupt.');
        } catch (\RuntimeException $exception) {
            self::assertSame('PAD5C10_INTERRUPTED_BEFORE_COMBINED_COMMIT', $exception->getMessage());
        }
        try {
            $service->reconstruct($productionId);
            self::fail('Before-commit cut left a durable winner.');
        } catch (\RuntimeException $exception) {
            self::assertSame('PST112_IMMUTABLE_RECORD_ABSENT', $exception->getMessage());
        }

        try {
            $this->produceThroughProof($basis, $proof, Proof::CUT_AFTER_COMBINED_COMMIT);
            self::fail('After-commit cut did not interrupt.');
        } catch (\RuntimeException $exception) {
            self::assertSame('PAD5C11_INTERRUPTED_AFTER_COMBINED_COMMIT', $exception->getMessage());
        }

        self::assertSame($productionId, $service->reconstruct($productionId)['production_id']);
    }

    public function testIneligibleExpiredAndRevokedBasesRefuseBeforeMutation(): void
    {
        $cases = [];

        $ineligible = $this->productionBasis();
        $ineligible['aggregate']['classification'] = 'REFUSED';
        $cases[] = [$ineligible, $ineligible['at']];

        $expired = $this->productionBasis();
        $cases[] = [$expired, $expired['at']->modify('+11 minutes')];

        $revoked = $this->productionBasis();
        $revoked['authorization']['revocation'] = self::referenceRecord('revocation');
        $revoked['authorization'] = self::seal($revoked['authorization']);
        $revoked['envelope']['issuance_authorization'] =
            self::reference($revoked['authorization'], 'issuance_authorization_id');
        $revoked['envelope']['source_authority'] = $revoked['envelope']['issuance_authorization'];
        $revoked['envelope']['validity']['revocation_reference'] = $revoked['authorization']['revocation'];
        $revoked['envelope'] = self::seal($revoked['envelope']);
        $revoked['aggregate']['references']['issuance_authorization'] =
            self::reference($revoked['authorization'], 'issuance_authorization_id');
        $cases[] = [$revoked, $revoked['at']];

        foreach ($cases as [$basis, $at]) {
            try {
                $this->produce($basis, new Production($this->root), $at);
                self::fail('Ineligible production basis was accepted.');
            } catch (\RuntimeException $exception) {
                self::assertSame('PAD5C00_PRODUCTION_NOT_ELIGIBLE', $exception->getMessage());
            }
        }
    }

    public function testProductionSourceHasNoProviderCredentialOrActivationDependency(): void
    {
        $root = dirname(__DIR__, 3);
        $source = (string) file_get_contents(
            $root.'/src/Imperium/Runtime/Imperator/PrincipalActivationDecisionAuthorityProvenanceProductionService.php',
        );
        $source .= (string) file_get_contents(
            $root.'/src/Imperium/Runtime/Imperator/PrincipalActivationDecisionAuthorityProvenanceProductionInterruptionProofService.php',
        );

        foreach ([
            'AuthorityConsumptionStore',
            'CredentialCapability',
            'CredentialBroker',
            'AgentMailEmailTransport',
            'ProviderExecutorPrincipalActivationService',
            'ProviderBindingActivation',
            'external_io',
            'IronGate',
            'Lazaretto',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testDocumentationAuthorizesAdversarialAuditOnly(): void
    {
        $doc = $this->document('docs/principal-activation-decision-authority-provenance-remediation-batch-5c-production.md');
        $handoff = $this->document('docs/handoffs/principal-activation-decision-authority-provenance-remediation-batch-5c-complete.md');

        foreach ([
            'BATCH_5C_ATOMIC_SUCCESSOR_ACTIVATION_AND_DECISION_AUTHORITY_PRODUCTION_COMPLETE',
            'one immutable combined winner',
            'consumes exactly one decision-issuance authorization',
            'one unconsumed single-use activation authority',
            'no consumption-only or decision-only durable state',
            'before-commit interruption leaves no winner',
            'after-commit interruption leaves the exact reconstructable winner',
        ] as $claim) {
            self::assertNotFalse(stripos($doc, $claim), $claim);
        }
        foreach ([
            'Only remediation Batch 6 adversarial audit may next be considered',
            'may not create or repair a production record',
            'issue or consume authority',
            'Provider Effect Principal and Binding Activation remains paused',
            'Iron Gate and Lazaretto remain closed',
            'two batches',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }

    private function productionBasis(): array
    {
        $fixtures = $this->fixtures();
        $at = new \DateTimeImmutable('2026-08-30T20:05:00+00:00');
        $activation = self::seal([
            'schema' => 'imperium.imperator-principal-lifecycle-disposition/v1',
            'disposition_id' => 'activation-disposition',
            'instance_id' => 'imperium-test',
            'source_status' => 'PENDING_ACTIVATION',
            'disposition' => 'ACTIVATE',
            'effective_at' => '2026-08-30T20:04:00+00:00',
            'authority_scope_changed' => false,
            'external_action_performed' => false,
            'sealed' => true,
        ]);
        $aggregate = [
            'schema' => Aggregate::SCHEMA,
            'instance_id' => 'imperium-test',
            'classification' => 'ELIGIBLE',
            'reasons' => ['COMPLETE_EXACT_OFFLINE_DECISION_AUTHORITY_PROVENANCE_BASIS'],
            'references' => [
                'activation_disposition' => self::reference($activation, 'disposition_id'),
                'issuance_authorization' =>
                    self::reference($fixtures['authorization'], 'issuance_authorization_id'),
            ],
            'interruption_coverage' => [],
            'reconstructed_at' => '2026-08-30T20:04:30+00:00',
            'read_only' => true,
            'record_created' => false,
            'record_repaired' => false,
            'scope_granted' => false,
            'authority_issued' => false,
            'authority_consumed' => false,
            'principal_created' => false,
            'principal_activated' => false,
            'binding_activated' => false,
            'activation_decision_created' => false,
            'source_artifact_mutated' => false,
            'credential_or_capability_handled' => false,
            'provider_invoked' => false,
            'external_action_performed' => false,
        ];

        return $fixtures + compact('at', 'activation', 'aggregate');
    }

    private function produce(
        array $basis,
        ?Production $service = null,
        ?\DateTimeImmutable $at = null,
    ): array {
        return ($service ?? new Production($this->root))->produce(
            $basis['aggregate'],
            $basis['source'],
            $basis['transition'],
            $basis['activation'],
            $basis['principal'],
            $basis['envelope'],
            $basis['authorization'],
            $at ?? $basis['at'],
        );
    }

    private function produceThroughProof(array $basis, Proof $proof, ?string $cut): array
    {
        return $proof->produce(
            $basis['aggregate'],
            $basis['source'],
            $basis['transition'],
            $basis['activation'],
            $basis['principal'],
            $basis['envelope'],
            $basis['authorization'],
            $basis['at'],
            $cut,
        );
    }

    private function productionId(array $basis): string
    {
        $target = implode(':', [
            $basis['aggregate']['instance_id'],
            $basis['principal']['principal_version_id'],
            $basis['authorization']['issuance_authorization_id'],
            $basis['envelope']['decision_id'],
        ]);

        return 'decision-provenance-production-'.hash('sha256', $target);
    }
}
