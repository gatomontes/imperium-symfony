<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ProviderExecutionBoundaryRedesignInertIssuanceService;
use App\Imperium\Runtime\Imperator\SingleOperationProviderBindingActivationIssuanceContract;
use App\Imperium\Runtime\LaCortine\ProviderExecutionBoundaryContract;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalContract;
use App\Imperium\Runtime\LaCortine\ProviderImplementationBindingContract;
use App\Imperium\Runtime\LaCortine\ProviderImplementationBindingService;
use App\Imperium\Runtime\LaCortine\SingleOperationProviderBindingActivationContract;
use App\Imperium\Runtime\LaCortine\SingleOperationProviderBindingActivationIssuanceService;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use PHPUnit\Framework\TestCase;

final class ProviderExecutionBoundaryRedesignBatch4Test extends TestCase
{
    private string $root;
    private ImmutableRecordStore $records;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-peb-b4-'.bin2hex(random_bytes(6));
        $atomic = new AtomicTransition($this->root);
        $this->records = new ImmutableRecordStore($this->root, $atomic);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->root)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->root,
                \FilesystemIterator::SKIP_DOTS,
            ),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->root);
    }

    public function testExactActivationIsIssuedUnconsumedAndReplaysExactly(): void
    {
        $at = new \DateTimeImmutable('2026-08-30T16:00:00+00:00');
        $candidate = $this->seedBasis($at);
        $decision = $this->decision($candidate, $at);
        $this->records->put(
            SingleOperationProviderBindingActivationIssuanceService::DECISIONS,
            $decision['decision_id'],
            $decision,
        );

        $service = new SingleOperationProviderBindingActivationIssuanceService($this->root);
        $issuance = $service->activate($decision['decision_id'], $candidate, $at);
        $replay = $service->activate($decision['decision_id'], $candidate, $at);

        self::assertSame($issuance, $replay);
        self::assertTrue($issuance['binding_activation_issued']);
        self::assertFalse($issuance['principal_installed']);
        self::assertFalse($issuance['provider_binding_activated']);
        self::assertFalse($issuance['credential_capability_issued']);
        self::assertFalse($issuance['credential_resolved']);
        self::assertFalse($issuance['external_action_performed']);

        $activation = $this->records->read(
            SingleOperationProviderBindingActivationIssuanceService::ACTIVATIONS,
            'single-operation-provider-binding-activation-1',
        );
        self::assertSame('ACTIVATED_UNCONSUMED', $activation['status']);
        self::assertTrue($activation['single_operation']);
        self::assertTrue($activation['activation_authority_consumption']['consumed']);
        self::assertFalse($activation['activation_authority_consumption']['continuing_authority']);
        self::assertSame($candidate['request'], $activation['request']);
        self::assertSame($candidate['scope'], $activation['scope']);

        $binding = $this->records->read(
            ProviderImplementationBindingService::BINDINGS,
            $candidate['provider_binding']['id'],
        );
        self::assertSame('BOUND_INACTIVE', $binding['status']);
        foreach (SingleOperationProviderBindingActivationContract::NON_AUTHORITIES as $permission) {
            self::assertFalse($permission);
        }
    }

    public function testChangedPayloadAfterAuthorizationRefusesBeforeCommit(): void
    {
        $at = new \DateTimeImmutable('2026-08-30T16:00:00+00:00');
        $candidate = $this->seedBasis($at);
        $decision = $this->decision($candidate, $at);
        $this->records->put(
            SingleOperationProviderBindingActivationIssuanceService::DECISIONS,
            $decision['decision_id'],
            $decision,
        );
        $candidate['request']['payload_digest'] = str_repeat('f', 64);

        $this->expectExceptionMessage('PEB409_ACTIVATION_INVALID');
        (new SingleOperationProviderBindingActivationIssuanceService($this->root))
            ->activate($decision['decision_id'], $candidate, $at);
    }

    public function testSourceContainsNoExecutionOrCredentialDependency(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3)
            .'/src/Imperium/Runtime/LaCortine/'
            .'SingleOperationProviderBindingActivationIssuanceService.php',
        );

        foreach ([
            "'status' => 'ACTIVATED_UNCONSUMED'",
            "'single_operation' => true",
            "'binding_activation_issued' => true",
            "'principal_installed' => false",
            "'provider_binding_activated' => false",
            "'credential_capability_issued' => false",
            "'credential_resolved' => false",
            "'external_action_performed' => false",
        ] as $proof) {
            self::assertStringContainsString($proof, $source);
        }
        foreach ([
            'DurableProviderExecutionAuthorityContract',
            'EnvironmentCredentialBroker',
            'CredentialCapability',
            'DeterministicTransport',
            'AgentMail',
            'IronGate',
            'Lazaretto',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testBatchDocumentationAuthorizesOnlyDurableAuthorityNext(): void
    {
        $handoff = preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(
                dirname(__DIR__, 3)
                .'/docs/handoffs/provider-execution-boundary-redesign-batch-4-complete.md',
            ),
        );

        foreach ([
            'Only Batch 5 may next be considered',
            'durable provider-execution authority',
            'may not consume that authority',
            'atomic execution admission',
            'credential or capability',
            'external I/O',
            'Iron Gate',
            'Lazaretto',
            'Provider Execution Assurance remains paused',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }

    private function seedBasis(\DateTimeImmutable $at): array
    {
        $boundary = $this->records->put(
            ProviderExecutionBoundaryRedesignInertIssuanceService::BOUNDARIES,
            'provider-execution-boundary-1',
            [
                'schema' => ProviderExecutionBoundaryContract::SCHEMA,
                'boundary_id' => 'provider-execution-boundary-1',
                'instance_id' => 'instance-1',
                'deployment_boundary' => [
                    'boundary_kind' => 'SAME_PROCESS_GOVERNED_EXECUTOR',
                    'process_isolation_required' => false,
                    'credential_possession_stationary' => true,
                    'cross_process_capability_transfer_required' => false,
                ],
                'authoritative_root' => 'imperium.single-authoritative-root',
                'credential_posture' => [
                    'credential_owner' => 'deployment.environment',
                    'credential_reference_persistence_permitted' => false,
                    'credential_secret_persistence_permitted' => false,
                    'credential_reconstruction_permitted' => false,
                ],
                'executor_principal_requirements' => [
                    'exact_principal_required' => true,
                ],
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
                'defined_at' => $at->format(DATE_ATOM),
                'sealed' => true,
            ],
        );
        $boundaryReference = $this->recordReference($boundary, 'boundary_id');
        $principal = $this->records->put(
            ProviderExecutionBoundaryRedesignInertIssuanceService::PRINCIPAL_ATTESTATIONS,
            'provider-executor-principal-attestation-1',
            [
                'schema' => ProviderExecutorPrincipalContract::SCHEMA,
                'principal_attestation_id' => 'provider-executor-principal-attestation-1',
                'instance_id' => 'instance-1',
                'execution_boundary' => $boundaryReference,
                'principal' => [
                    'principal_id' => 'provider-executor-principal-1',
                    'infrastructure_role' => 'provider-executor',
                    'binding_id' => 'provider-executor-binding-1',
                    'generation' => 1,
                    'process_boundary_id' => $boundary['boundary_id'],
                ],
                'source_attestation' => $this->reference('source-attestation-1'),
                'competence' => [
                    'operation' => 'email.send',
                    'provider_id' => 'agentmail',
                    'adapter_id' => 'agentmail-email-transport',
                    'credential_family' => 'agentmail-api-token',
                    'same_process_execution_required' => true,
                ],
                'validity' => [
                    'effective_at' => $at->format(DATE_ATOM),
                    'expires_at' => $at->modify('+10 minutes')->format(DATE_ATOM),
                    'revocation_reference' => null,
                ],
                'status' => 'ATTESTED_INERT',
                'attested_at' => $at->format(DATE_ATOM),
                'sealed' => true,
            ],
        );
        $tool = $this->reference('canonical-email-send.v1');
        $assurance = $this->reference('agentmail-assurance-profile-1');
        $destinationPolicy = [
            'id' => 'email-destination-policy-1',
            'digest' => str_repeat('b', 64),
            'schema' => 'imperium.la-cortine.destination-policy/v1',
        ];
        $binding = $this->records->put(
            ProviderImplementationBindingService::BINDINGS,
            'provider-implementation-binding-1',
            [
                'schema' => ProviderImplementationBindingContract::SCHEMA,
                'binding_id' => 'provider-implementation-binding-1',
                'instance_id' => 'instance-1',
                'source_authority' => $this->reference('provider-binding-authority-1'),
                'tool_operation' => $tool,
                'provider_implementation' => [
                    'provider_id' => 'agentmail',
                    'adapter_id' => 'agentmail-email-transport',
                    'adapter_version' => 'v1',
                ],
                'assurance_profile' => $assurance,
                'credential_family' => [
                    'family_id' => 'agentmail-api-token',
                    'provider_id' => 'agentmail',
                    'secret_persistence_permitted' => false,
                ],
                'request_encoder' => $this->reference('agentmail-request-encoder-1'),
                'evidence_decoder' => $this->reference('agentmail-evidence-decoder-1'),
                'destination_policy' => [
                    'policy_id' => $destinationPolicy['id'],
                    'policy_digest' => $destinationPolicy['digest'],
                    'exact_destination_required' => true,
                ],
                'scope' => [
                    'operation' => 'email.send',
                    'authorization_target_id' => 'effect-authorization-1',
                    'authorization_target_digest' => str_repeat('a', 64),
                    'provider_substitution_permitted' => false,
                ],
                'validity' => [
                    'effective_at' => $at->format(DATE_ATOM),
                    'expires_at' => $at->modify('+10 minutes')->format(DATE_ATOM),
                ],
                'status' => 'BOUND_INACTIVE',
                'bound_at' => $at->format(DATE_ATOM),
                'sealed' => true,
            ],
        );

        return [
            'execution_boundary' => $boundaryReference,
            'executor_principal' => $this->recordReference(
                $principal,
                'principal_attestation_id',
            ),
            'provider_binding' => $this->recordReference($binding, 'binding_id'),
            'tool_authority' => $tool,
            'effect_authorization' => $this->reference('effect-authorization-1'),
            'request_reference' => $this->reference('provider-request-1'),
            'request' => [
                'request_id' => 'provider-request-1',
                'operation' => 'email.send',
                'exact_destination' => 'https://api.agentmail.example/send',
                'payload_digest' => str_repeat('c', 64),
                'request_fingerprint' => str_repeat('d', 64),
            ],
            'assurance_profile' => $assurance,
            'destination_policy' => $destinationPolicy,
            'scope' => [
                'execution_id' => 'provider-execution-1',
                'provider_id' => 'agentmail',
                'adapter_id' => 'agentmail-email-transport',
                'provider_substitution_permitted' => false,
                'request_substitution_permitted' => false,
            ],
        ];
    }

    private function decision(array $candidate, \DateTimeImmutable $at): array
    {
        $contract = SingleOperationProviderBindingActivationIssuanceContract::class;
        $decisionId = 'single-operation-provider-binding-activation-decision-aaaaaaaaaaaaaaaaaaaa';
        $basis = [
            'execution_boundary' => $candidate['execution_boundary'],
            'executor_principal' => $candidate['executor_principal'],
            'provider_binding' => $candidate['provider_binding'],
            'tool_authority' => $candidate['tool_authority'],
            'effect_authorization' => $candidate['effect_authorization'],
            'request' => $candidate['request_reference'],
            'destination_policy' => $candidate['destination_policy'],
            'assurance_profile' => $candidate['assurance_profile'],
        ];
        $candidateDigest = hash('sha256', CanonicalJson::encode($candidate));

        return $this->seal([
            'schema' => $contract::DECISION_SCHEMA,
            'decision_id' => $decisionId,
            'instance_id' => 'instance-1',
            'source_authority' => $this->reference('source-activation-authority-1'),
            'actor' => [
                'principal_id' => 'imperator-principal-1',
                'office' => 'imperator',
                'seat' => 'imperator',
                'binding_id' => 'imperator-binding-1',
                'generation' => 1,
            ],
            'target' => [
                'kind' => $contract::TARGET_KIND,
                'id' => 'single-operation-provider-binding-activation-1',
                'digest' => $candidateDigest,
                'schema' => SingleOperationProviderBindingActivationContract::SCHEMA,
            ],
            'basis' => $basis,
            'disposition' => 'AUTHORIZED',
            'rationale' => 'One exact inert activation may be issued.',
            'limitations' => 'No execution authority, credential use or I/O.',
            'issuance_authority' => [
                'authority_id' => 'single-operation-binding-activation-issuance-authority-1',
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'issuer_service' => 'la-cortine.single-operation-binding-activation-issuer',
                'permitted_transition' => $contract::PERMITTED_TRANSITION,
                'target_digest' => $candidateDigest,
                'expires_at' => $at->modify('+10 minutes')->format(DATE_ATOM),
                'consumed' => false,
                'continuing_authority' => false,
            ],
            'decided_at' => $at->format(DATE_ATOM),
            'expires_at' => $at->modify('+10 minutes')->format(DATE_ATOM),
            'external_action_performed' => false,
            'sealed' => true,
        ]);
    }

    private function recordReference(array $record, string $idField): array
    {
        return [
            'id' => $record[$idField],
            'digest' => $record['record_digest'],
            'schema' => $record['schema'],
        ];
    }

    private function reference(string $id): array
    {
        return [
            'id' => $id,
            'digest' => str_repeat('a', 64),
            'schema' => 'imperium.test.reference/v1',
        ];
    }

    private function seal(array $record): array
    {
        unset($record['record_digest']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }
}
