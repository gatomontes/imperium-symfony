<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\ProviderExecutorPrincipalActivationDecisionContract;
use App\Imperium\Runtime\LaCortine\ProviderExecutionBoundaryContract;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationContract;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationContractValidator;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationFixtureStore;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalContract;

class ProviderExecutionEffectReadinessBatch7Test extends ProviderExecutionEffectReadinessBatch2Test
{
    public function testExactOfflineDecisionAndActivationValidateStoreAndReplay(): void
    {
        $fixtures = $this->fixtures();
        $store = new ProviderExecutorPrincipalActivationFixtureStore($this->root);

        self::assertSame(
            $fixtures['decision'],
            $store->putDecision(
                $fixtures['decision'],
                $fixtures['attestation'],
                $fixtures['assurance'],
                $fixtures['boundary'],
                $fixtures['decidedAt'],
            ),
        );
        self::assertSame(
            $fixtures['activation'],
            $store->putActivation(
                $fixtures['activation'],
                $fixtures['decision'],
                $fixtures['attestation'],
                $fixtures['assurance'],
                $fixtures['boundary'],
                $fixtures['activatedAt']->modify('+1 minute'),
            ),
        );
        self::assertSame(
            $fixtures['activation'],
            $store->putActivation(
                $fixtures['activation'],
                $fixtures['decision'],
                $fixtures['attestation'],
                $fixtures['assurance'],
                $fixtures['boundary'],
                $fixtures['activatedAt']->modify('+1 minute'),
            ),
        );
    }

    public function testChangedGenerationAssuranceOrAuthorityFailsClosed(): void
    {
        $fixtures = $this->fixtures();
        $validator = new ProviderExecutorPrincipalActivationContractValidator();

        $generation = $fixtures['decision'];
        $generation['scope']['principal_generation'] = 2;
        $generation = self::seal($generation);

        $assurance = $fixtures['assurance'];
        $assurance['status'] = 'REVOKED';
        $assurance = self::seal($assurance);

        $authority = $fixtures['activation'];
        $authority['consumed_activation_authority']['id'] = 'changed-authority';
        $authority = self::seal($authority);

        foreach ([
            fn () => $validator->assertDecision(
                $generation,
                $fixtures['attestation'],
                $fixtures['assurance'],
                $fixtures['boundary'],
                $fixtures['decidedAt'],
            ),
            fn () => $validator->assertDecision(
                $fixtures['decision'],
                $fixtures['attestation'],
                $assurance,
                $fixtures['boundary'],
                $fixtures['decidedAt'],
            ),
            fn () => $validator->assertActivation(
                $authority,
                $fixtures['decision'],
                $fixtures['attestation'],
                $fixtures['assurance'],
                $fixtures['boundary'],
                $fixtures['activatedAt']->modify('+1 minute'),
            ),
        ] as $attempt) {
            try {
                $attempt();
                self::fail('Invalid principal activation evidence accepted.');
            } catch (\RuntimeException $exception) {
                self::assertStringStartsWith('PEA7', $exception->getMessage());
            }
        }
    }

    public function testRefusedDecisionCarriesNoAuthorityAndCannotBackActivation(): void
    {
        $fixtures = $this->fixtures();
        $decision = $fixtures['decision'];
        $decision['disposition'] = 'REFUSED';
        $decision['activation_authority'] = null;
        $decision = self::seal($decision);
        $validator = new ProviderExecutorPrincipalActivationContractValidator();

        $validator->assertDecision(
            $decision,
            $fixtures['attestation'],
            $fixtures['assurance'],
            $fixtures['boundary'],
            $fixtures['decidedAt'],
        );
        self::addToAssertionCount(1);

        $this->expectExceptionMessage('PEA710_PRINCIPAL_ACTIVATION_INVALID');
        $validator->assertActivation(
            $fixtures['activation'],
            $decision,
            $fixtures['attestation'],
            $fixtures['assurance'],
            $fixtures['boundary'],
            $fixtures['activatedAt']->modify('+1 minute'),
        );
    }

    public function testFixtureStoreContainsNoCredentialProviderOrActivationProducer(): void
    {
        $root = dirname(__DIR__, 3);
        $sources = (string) file_get_contents(
            $root.'/src/Imperium/Runtime/LaCortine/'
                .'ProviderExecutorPrincipalActivationContractValidator.php',
        );
        $sources .= (string) file_get_contents(
            $root.'/src/Imperium/Runtime/LaCortine/'
                .'ProviderExecutorPrincipalActivationFixtureStore.php',
        );

        foreach ([
            'CredentialCapability',
            'CredentialBroker',
            'AgentMailEmailTransport',
            'GovernedProviderExecutionCombinedAdmissionService',
            'DurableProviderExecutionAuthorityIssuanceService',
            'AuthorityConsumptionStore',
            'public function activate',
            'public function issue',
            'public function consume',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $sources);
        }
    }

    public function testDocumentationKeepsValidationOfflineAndRuntimeClosed(): void
    {
        $doc = $this->document(
            'docs/provider-execution-effect-readiness-principal-activation-validation.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-execution-effect-readiness-batch-7-complete.md',
        );

        foreach ([
            'BATCH_7_FAIL_CLOSED_PRINCIPAL_ACTIVATION_FIXTURE_VALIDATION_COMPLETE',
            'caller-supplied offline fixtures',
            'carries no activation authority',
            'does not produce a competent decision',
            'consume activation authority',
            'principal remains inert',
            'provider binding remains inactive',
            'UNKNOWN_REPLAY_PROHIBITED',
        ] as $finding) {
            self::assertNotFalse(stripos($doc, $finding), $finding);
        }

        foreach ([
            'Only Batch 8 may next be considered',
            'offline interruption',
            'exact replay',
            'changed-evidence conflict',
            'same-root contention proof',
            'no provider was invoked',
            'Iron Gate',
            'Lazaretto',
            'approximately three batches',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }

    protected function fixtures(): array
    {
        $decidedAt = new \DateTimeImmutable('2026-08-30T12:10:00+00:00');
        $activatedAt = $decidedAt;
        $source = $this->source();
        $profile = $this->profile([$source]);
        $assurance = $this->admission($profile, [$source]);

        $boundary = self::seal([
            'schema' => ProviderExecutionBoundaryContract::SCHEMA,
            'boundary_id' => 'provider-execution-boundary-1',
            'instance_id' => 'imperium-test',
            'deployment_boundary' => [
                'boundary_kind' => 'SAME_PROCESS_GOVERNED_EXECUTOR',
                'process_isolation_required' => false,
                'credential_possession_stationary' => true,
                'cross_process_capability_transfer_required' => false,
            ],
            'authoritative_root' => 'imperium-test-root',
            'credential_posture' => [
                'credential_owner' => 'deployment',
                'credential_reference_persistence_permitted' => false,
                'credential_secret_persistence_permitted' => false,
                'credential_reconstruction_permitted' => false,
            ],
            'executor_principal_requirements' => ['exact_generation' => true],
            'admission_ordering' => [
                'authority_consumed_pre_resolution' => true,
                'effect_start_committed_pre_resolution' => true,
                'effect_start_committed_pre_io' => true,
                'credential_resolution_inside_winning_boundary' => true,
            ],
            'threat_model' => [
                'integrity_posture' => 'TRUSTED_WRITER_CANONICAL_INTEGRITY',
                'deployment_posture' => 'SINGLE_AUTHORITATIVE_ROOT_ONLY',
                'hostile_writer_non_forgeability_claimed' => false,
                'multi_host_consensus_claimed' => false,
                'split_brain_resistance_claimed' => false,
            ],
            'status' => 'DEFINED_INERT',
            'defined_at' => '2026-08-30T12:00:00+00:00',
            'sealed' => true,
        ]);

        $attestation = self::seal([
            'schema' => ProviderExecutorPrincipalContract::SCHEMA,
            'principal_attestation_id' => 'provider-executor-principal-attestation-1',
            'instance_id' => 'imperium-test',
            'execution_boundary' => self::reference($boundary, 'boundary_id'),
            'principal' => [
                'principal_id' => 'provider-executor-principal-1',
                'infrastructure_role' => 'same-process-provider-executor',
                'binding_id' => 'provider-executor-binding-1',
                'generation' => 1,
                'process_boundary_id' => $boundary['boundary_id'],
            ],
            'source_attestation' => $this->arbitraryReference('source-attestation-1'),
            'competence' => [
                'operation' => 'email.send',
                'provider_id' => 'agentmail',
                'adapter_id' => 'agentmail-email-transport',
                'credential_family' => 'agentmail-api-key',
                'same_process_execution_required' => true,
            ],
            'validity' => [
                'effective_at' => '2026-08-30T12:00:00+00:00',
                'expires_at' => '2026-08-30T13:00:00+00:00',
                'revocation_reference' => null,
            ],
            'status' => 'ATTESTED_INERT',
            'attested_at' => '2026-08-30T12:00:00+00:00',
            'sealed' => true,
        ]);

        $validity = [
            'effective_at' => $decidedAt->format(DATE_ATOM),
            'expires_at' => $decidedAt->modify('+10 minutes')->format(DATE_ATOM),
            'revocation_reference' => null,
        ];
        $scope = [
            'provider_id' => 'agentmail',
            'operation' => 'email.send',
            'execution_boundary_id' => $boundary['boundary_id'],
            'principal_id' => $attestation['principal']['principal_id'],
            'principal_generation' => 1,
            'process_boundary_id' => $boundary['boundary_id'],
            'same_process_execution_required' => true,
        ];
        $authority = [
            'authority_id' => 'principal-activation-authority-1',
            'authority_single_use' => true,
            'authority_exercisable' => true,
            'issuer_service' => 'imperator.provider-executor-principal-activation',
            'permitted_transition' => ProviderExecutorPrincipalActivationDecisionContract::PERMITTED_TRANSITION,
            'target_attestation_digest' => $attestation['record_digest'],
            'expires_at' => $validity['expires_at'],
            'consumed' => false,
            'continuing_authority' => false,
        ];
        $decision = self::seal([
            'schema' => ProviderExecutorPrincipalActivationDecisionContract::SCHEMA,
            'decision_id' => 'provider-executor-principal-activation-decision-1',
            'instance_id' => 'imperium-test',
            'source_authority' => $this->arbitraryReference('source-authority-1'),
            'actor' => [
                'principal_id' => 'imperator-principal-1',
                'office' => 'imperator',
                'seat' => 'imperator',
                'binding_id' => 'imperator-binding-1',
                'generation' => 1,
            ],
            'principal_attestation' => self::reference(
                $attestation,
                'principal_attestation_id',
            ),
            'provider_assurance_admission' => self::reference(
                $assurance,
                'admission_id',
            ),
            'scope' => $scope,
            'disposition' => 'AUTHORIZED',
            'rationale' => 'Exact inert principal generation is eligible for future activation.',
            'limitations' => 'No provider binding, execution authority, credential, or I/O.',
            'activation_authority' => $authority,
            'validity' => $validity,
            'decided_at' => $decidedAt->format(DATE_ATOM),
            'external_action_performed' => false,
            'sealed' => true,
        ]);

        $activation = self::seal([
            'schema' => ProviderExecutorPrincipalActivationContract::SCHEMA,
            'principal_activation_id' => 'provider-executor-principal-activation-1',
            'instance_id' => 'imperium-test',
            'source_decision' => self::reference($decision, 'decision_id'),
            'consumed_activation_authority' => [
                'id' => $authority['authority_id'],
                'digest' => $decision['record_digest'],
                'schema' => $decision['schema'],
                'consumed_at' => $activatedAt->format(DATE_ATOM),
                'consumed' => true,
                'continuing_authority' => false,
            ],
            'provider_assurance_admission' => self::reference(
                $assurance,
                'admission_id',
            ),
            'execution_boundary' => self::reference($boundary, 'boundary_id'),
            'principal_attestation' => self::reference(
                $attestation,
                'principal_attestation_id',
            ),
            'principal' => $attestation['principal'],
            'scope' => [
                'provider_id' => $scope['provider_id'],
                'operation' => $scope['operation'],
                'same_process_execution_required' => true,
                'provider_substitution_permitted' => false,
                'operation_substitution_permitted' => false,
                'principal_generation_substitution_permitted' => false,
            ],
            'validity' => $validity,
            'reconstruction' => [
                'read_only' => true,
                'exact_replay_only' => true,
                'reactivation_permitted' => false,
                'generation_upgrade_permitted' => false,
            ],
            'status' => 'ACTIVE',
            'activated_at' => $activatedAt->format(DATE_ATOM),
            'sealed' => true,
        ]);

        return compact(
            'boundary',
            'attestation',
            'assurance',
            'decision',
            'activation',
            'decidedAt',
            'activatedAt',
        );
    }

    private function arbitraryReference(string $id): array
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
