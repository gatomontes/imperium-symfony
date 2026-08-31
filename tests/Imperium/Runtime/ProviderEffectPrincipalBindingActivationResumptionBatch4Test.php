<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\PrincipalActivationDecisionAuthorityProvenanceProductionContract as Production;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationCanonicalFixtureStore;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationCanonicalInputContract as Input;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationCanonicalResolutionAdmissionContract as Admission;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationService;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalCanonicalActivationService;

final class ProviderEffectPrincipalBindingActivationResumptionBatch4Test
    extends ProviderExecutionEffectReadinessBatch7Test
{
    public function testReadyCanonicalChainConsumesExactAuthorityAndActivatesExactPrincipal(): void
    {
        $f = $this->canonicalFixtures();
        $this->storeCanonicalChain($f);
        $service = new ProviderExecutorPrincipalCanonicalActivationService($this->root);

        $activation = $this->activate($service, $f);

        self::assertSame('ACTIVE', $activation['status']);
        self::assertSame($f['attestation']['principal'], $activation['principal']);
        self::assertSame(
            $f['decision']['activation_authority']['authority_id'],
            $activation['consumed_activation_authority']['id'],
        );
        self::assertTrue($activation['consumed_activation_authority']['consumed']);
        self::assertFalse($activation['consumed_activation_authority']['continuing_authority']);
        self::assertSame($activation, $this->activate($service, $f));
    }

    public function testTwoCanonicalEntrypointsConvergeOnOneCombinedWinner(): void
    {
        $f = $this->canonicalFixtures();
        $this->storeCanonicalChain($f);
        $left = new ProviderExecutorPrincipalCanonicalActivationService($this->root);
        $right = new ProviderExecutorPrincipalCanonicalActivationService($this->root);

        $winner = $this->activate($left, $f);
        self::assertSame($winner, $this->activate($right, $f));

        $files = glob(
            $this->root.'/'.ProviderExecutorPrincipalActivationService::ACTIVATIONS.'/*.json',
        ) ?: [];
        self::assertCount(1, $files);
    }

    public function testAbsenceExpiryRevocationAndChangedEvidenceRefuseBeforeActivation(): void
    {
        $f = $this->canonicalFixtures();
        $service = new ProviderExecutorPrincipalCanonicalActivationService($this->root);

        try {
            $this->activate($service, $f);
            self::fail('Absent canonical fixtures activated a principal.');
        } catch (\RuntimeException $exception) {
            self::assertSame('PRA400_CANONICAL_ACTIVATION_NOT_READY', $exception->getMessage());
        }

        $this->storeCanonicalChain($f);
        foreach (['expired', 'revoked', 'changed'] as $case) {
            $candidate = $f;
            if ('expired' === $case) {
                $candidate['activatedAt'] = $f['activatedAt']->modify('+11 minutes');
            } elseif ('revoked' === $case) {
                $candidate['decision']['validity']['revocation_reference'] =
                    self::referenceRecord('revocation-1');
                $candidate['decision'] = self::seal($candidate['decision']);
            } else {
                $candidate['decision']['scope']['principal_generation'] = 2;
                $candidate['decision'] = self::seal($candidate['decision']);
            }

            try {
                $this->activate($service, $candidate);
                self::fail('Invalid canonical chain activated a principal: '.$case);
            } catch (\RuntimeException $exception) {
                self::assertSame('PRA400_CANONICAL_ACTIVATION_NOT_READY', $exception->getMessage(), $case);
            }
        }

        self::assertSame(
            [],
            glob($this->root.'/'.ProviderExecutorPrincipalActivationService::ACTIVATIONS.'/*.json') ?: [],
        );
    }

    public function testCanonicalEntrypointCannotActivateBindingHandleCredentialOrInvokeProvider(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3)
                .'/src/Imperium/Runtime/LaCortine/'
                .'ProviderExecutorPrincipalCanonicalActivationService.php',
        );

        foreach ([
            'ProviderBindingActivationService',
            'CredentialCapability',
            'EnvironmentCredentialBroker',
            'AgentMailEmailTransport',
            'GovernedProviderExecutionCombinedAdmissionService',
            'DurableProviderExecutionAuthority',
            'external_io',
            'IronGate',
            'Lazaretto',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testDocumentationAuthorizesAdversarialAuditNext(): void
    {
        $doc = $this->document(
            'docs/provider-effect-principal-binding-activation-resumption-batch-4-entry-point.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-effect-principal-binding-activation-resumption-batch-4-complete.md',
        );

        foreach ([
            'RESUMPTION_BATCH_4_CANONICAL_ATOMIC_PRINCIPAL_ACTIVATION_ENTRY_POINT_COMPLETE',
            'READY_OFFLINE_ACTIVATION_INPUT',
            'single combined authority-consumption and principal-activation winner',
            'exact replay',
            'provider binding remains BOUND_INACTIVE',
            'no credential or capability',
            'no provider invocation',
        ] as $finding) {
            self::assertNotFalse(stripos($doc, $finding), $finding);
        }
        foreach ([
            'Only Provider Effect Principal and Binding Activation Resumption Batch 5',
            'adversarial audit',
            'contention',
            'expiry',
            'revocation',
            'crash',
            'reconstruction',
            'secret exclusion',
            'may not activate a provider binding',
            'Iron Gate',
            'Lazaretto',
            'approximately two batches',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }

    private function canonicalFixtures(): array
    {
        $f = $this->fixtures();
        $production = self::seal([
            'schema' => Production::SCHEMA,
            'production_id' => 'provenance-production-activation-1',
            'instance_id' => $f['decision']['instance_id'],
            'eligible_aggregate' => self::referenceRecord('eligible-aggregate-1'),
            'pending_successor_principal' => self::referenceRecord('successor-principal-1'),
            'applied_lifecycle_disposition' => self::referenceRecord('lifecycle-disposition-1'),
            'effective_principal_status' => 'PENDING_ACTIVATION',
            'consumed_issuance_authorization' => self::referenceRecord('issuance-authorization-1'),
            'activation_decision' => self::reference($f['decision'], 'decision_id'),
            'combined_winner' => self::referenceRecord('production-winner-1'),
            'produced_at' => $f['decidedAt']->format(DATE_ATOM),
            'provider_executor_principal_activated' => false,
            'provider_binding_activated' => false,
            'activation_authority_consumed' => false,
            'credential_or_capability_handled' => false,
            'provider_invoked' => false,
            'external_action_performed' => false,
            'continuing_authority' => false,
            'sealed' => true,
        ]);
        $principal = $f['attestation']['principal'];
        $scope = $f['decision']['scope'];
        $authority = $f['decision']['activation_authority'];
        $admission = self::seal([
            'schema' => Admission::SCHEMA,
            'resolution_admission_id' => 'canonical-resolution-admission-activation-1',
            'instance_id' => $f['decision']['instance_id'],
            'provenance_production' => self::reference($production, 'production_id'),
            'production_decision' => self::reference($f['decision'], 'decision_id'),
            'principal_attestation' => self::reference($f['attestation'], 'principal_attestation_id'),
            'provider_assurance_admission' => self::reference($f['assurance'], 'admission_id'),
            'execution_boundary' => self::reference($f['boundary'], 'boundary_id'),
            'activation_target' => [
                'principal_id' => $principal['principal_id'],
                'binding_id' => $principal['binding_id'],
                'generation' => $principal['generation'],
                'process_boundary_id' => $principal['process_boundary_id'],
                'provider_id' => $scope['provider_id'],
                'operation' => $scope['operation'],
            ],
            'activation_authority' => [
                'authority_id' => $authority['authority_id'],
                'decision_digest' => $f['decision']['record_digest'],
                'target_attestation_digest' => $f['attestation']['record_digest'],
                'effective_at' => $f['decision']['validity']['effective_at'],
                'expires_at' => $f['decision']['validity']['expires_at'],
                'revocation_reference' => $f['decision']['validity']['revocation_reference'],
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'consumed' => false,
                'continuing_authority' => false,
            ],
            'replay_contention_root' => [
                'root_id' => 'canonical-principal-activation-root-1',
                'instance_id' => $f['decision']['instance_id'],
                'principal_id' => $principal['principal_id'],
                'principal_generation' => $principal['generation'],
                'process_boundary_id' => $principal['process_boundary_id'],
                'production_id' => $production['production_id'],
                'decision_id' => $f['decision']['decision_id'],
                'authority_id' => $authority['authority_id'],
            ],
            'admitted_at' => $f['activatedAt']->format(DATE_ATOM),
            'exact_replay_only' => true,
            'changed_evidence_conflicts' => true,
            'resolution_required' => false,
            'activation_performed' => false,
            'authority_consumed' => false,
            'continuing_authority' => false,
            'sealed' => true,
        ]);
        $input = self::seal([
            'schema' => Input::SCHEMA,
            'input_id' => 'canonical-principal-activation-input-1',
            'instance_id' => $f['decision']['instance_id'],
            'resolution_admission' => self::reference($admission, 'resolution_admission_id'),
            'provenance_production' => $admission['provenance_production'],
            'production_decision' => $admission['production_decision'],
            'principal_attestation' => $admission['principal_attestation'],
            'provider_assurance_admission' => $admission['provider_assurance_admission'],
            'execution_boundary' => $admission['execution_boundary'],
            'activation_target' => $admission['activation_target'],
            'activation_authority' => $admission['activation_authority'],
            'replay_contention_root' => $admission['replay_contention_root'],
            'exact_replay_only' => true,
            'changed_evidence_conflicts' => true,
            'sealed' => true,
        ]);

        return $f + compact('production', 'admission', 'input');
    }

    private function storeCanonicalChain(array $f): void
    {
        $store = new ProviderExecutorPrincipalActivationCanonicalFixtureStore($this->root);
        $store->putResolutionAdmission(
            $f['admission'], $f['production'], $f['decision'], $f['attestation'],
            $f['assurance'], $f['boundary'], $f['activatedAt'],
        );
        $store->putActivationInput(
            $f['input'], $f['admission'], $f['production'], $f['decision'],
            $f['attestation'], $f['assurance'], $f['boundary'], $f['activatedAt'],
        );
    }

    private function activate(
        ProviderExecutorPrincipalCanonicalActivationService $service,
        array $f,
    ): array {
        return $service->activateCanonical(
            $f['admission']['resolution_admission_id'],
            $f['input']['input_id'],
            $f['production'],
            $f['decision'],
            $f['attestation'],
            $f['assurance'],
            $f['boundary'],
            $f['activatedAt'],
        );
    }

    private static function referenceRecord(string $id): array
    {
        return [
            'id' => $id,
            'digest' => str_repeat('a', 64),
            'schema' => 'imperium.test.reference/v1',
        ];
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
