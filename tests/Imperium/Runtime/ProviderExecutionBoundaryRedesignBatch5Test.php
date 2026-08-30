<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\DurableProviderExecutionAuthorityContract;
use App\Imperium\Runtime\Imperator\DurableProviderExecutionAuthorityIssuanceContract;
use App\Imperium\Runtime\Imperator\DurableProviderExecutionAuthorityIssuanceService;
use App\Imperium\Runtime\Imperator\ProviderExecutionBoundaryRedesignInertIssuanceService;
use App\Imperium\Runtime\LaCortine\ProviderExecutionBoundaryContract;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalContract;
use App\Imperium\Runtime\LaCortine\ProviderImplementationBindingContract;
use App\Imperium\Runtime\LaCortine\ProviderImplementationBindingService;
use App\Imperium\Runtime\LaCortine\SingleOperationProviderBindingActivationContract;
use App\Imperium\Runtime\LaCortine\SingleOperationProviderBindingActivationIssuanceService;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use PHPUnit\Framework\TestCase;

final class ProviderExecutionBoundaryRedesignBatch5Test extends TestCase
{
    private string $root;
    private ImmutableRecordStore $records;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-peb-b5-'.bin2hex(random_bytes(6));
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

    public function testExactDurableAuthorityIsIssuedUnconsumedAndReplaysExactly(): void
    {
        $at = new \DateTimeImmutable('2026-08-30T17:00:00+00:00');
        $candidate = $this->seedBasis($at);
        $decision = $this->decision($candidate, $at);
        $this->records->put(
            DurableProviderExecutionAuthorityIssuanceService::DECISIONS,
            $decision['decision_id'],
            $decision,
        );

        $service = new DurableProviderExecutionAuthorityIssuanceService($this->root);
        $issuance = $service->issue($decision['decision_id'], $candidate, $at);
        $replay = $service->issue($decision['decision_id'], $candidate, $at);

        self::assertSame($issuance, $replay);
        self::assertTrue($issuance['execution_authority_issued']);
        self::assertFalse($issuance['principal_installed']);
        self::assertFalse($issuance['provider_binding_activated']);
        self::assertFalse($issuance['credential_capability_issued']);
        self::assertFalse($issuance['credential_resolved']);
        self::assertFalse($issuance['external_action_performed']);

        $authority = $this->records->read(
            DurableProviderExecutionAuthorityIssuanceService::AUTHORITIES,
            'durable-provider-execution-authority-1',
        );
        self::assertTrue($authority['authority_single_use']);
        self::assertTrue($authority['authority_exercisable']);
        self::assertFalse($authority['consumed']);
        self::assertFalse($authority['continuing_authority']);
        self::assertSame(
            $candidate['provider_binding_activation'],
            $authority['provider_binding_activation'],
        );
        self::assertSame($candidate['request'], $authority['request']);
        foreach (DurableProviderExecutionAuthorityContract::NON_AUTHORITIES as $permission) {
            self::assertFalse($permission);
        }
    }

    public function testChangedDestinationAfterDecisionRefusesBeforeIssuance(): void
    {
        $at = new \DateTimeImmutable('2026-08-30T17:00:00+00:00');
        $candidate = $this->seedBasis($at);
        $decision = $this->decision($candidate, $at);
        $this->records->put(
            DurableProviderExecutionAuthorityIssuanceService::DECISIONS,
            $decision['decision_id'],
            $decision,
        );
        $candidate['request']['exact_destination'] = 'https://changed.example/send';

        $this->expectExceptionMessage('PEB510_EXECUTION_AUTHORITY_BASIS_INVALID');
        (new DurableProviderExecutionAuthorityIssuanceService($this->root))
            ->issue($decision['decision_id'], $candidate, $at);
    }

    public function testSourceContainsNoAuthorityConsumptionOrExecutionDependency(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3)
            .'/src/Imperium/Runtime/Imperator/'
            .'DurableProviderExecutionAuthorityIssuanceService.php',
        );

        foreach ([
            "'authority_single_use' => true",
            "'authority_exercisable' => true",
            "'consumed' => false",
            "'continuing_authority' => false",
            "'execution_authority_issued' => true",
            "'credential_capability_issued' => false",
            "'credential_resolved' => false",
            "'external_action_performed' => false",
        ] as $proof) {
            self::assertStringContainsString($proof, $source);
        }
        foreach ([
            'EnvironmentCredentialBroker',
            'CredentialCapability',
            'DeterministicTransport',
            'AgentMail',
            'IronGate',
            'Lazaretto',
            'EffectStart',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testBatchDocumentationAuthorizesOnlyAtomicAdmissionNext(): void
    {
        $handoff = preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(
                dirname(__DIR__, 3)
                .'/docs/handoffs/provider-execution-boundary-redesign-batch-5-complete.md',
            ),
        );

        foreach ([
            'Only Batch 6 may next be considered',
            'atomic execution admission',
            'consume the exact durable authority',
            'effect-start',
            'may not resolve a credential',
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
            $this->boundary($at),
        );
        $boundaryReference = $this->recordReference($boundary, 'boundary_id');
        $principal = $this->records->put(
            ProviderExecutionBoundaryRedesignInertIssuanceService::PRINCIPAL_ATTESTATIONS,
            'provider-executor-principal-attestation-1',
            $this->principal($boundaryReference, $at),
        );
        $principalReference = $this->recordReference(
            $principal,
            'principal_attestation_id',
        );
        $binding = $this->records->put(
            ProviderImplementationBindingService::BINDINGS,
            'provider-implementation-binding-1',
            $this->binding($at),
        );
        $bindingReference = $this->recordReference($binding, 'binding_id');
        $tool = $binding['tool_operation'];
        $effect = $this->reference('effect-authorization-1');
        $requestReference = $this->reference('provider-request-1');
        $destinationPolicy = [
            'id' => $binding['destination_policy']['policy_id'],
            'digest' => $binding['destination_policy']['policy_digest'],
            'schema' => 'imperium.la-cortine.destination-policy/v1',
        ];
        $activation = $this->records->put(
            SingleOperationProviderBindingActivationIssuanceService::ACTIVATIONS,
            'single-operation-provider-binding-activation-1',
            [
                'schema' => SingleOperationProviderBindingActivationContract::SCHEMA,
                'activation_id' => 'single-operation-provider-binding-activation-1',
                'instance_id' => 'instance-1',
                'source_activation_authority' => $this->reference(
                    'activation-issuance-authority-1',
                ),
                'execution_boundary' => $boundaryReference,
                'executor_principal' => $principalReference,
                'provider_binding' => $bindingReference,
                'tool_authority' => $tool,
                'effect_authorization' => $effect,
                'request' => [
                    'request_id' => 'provider-request-1',
                    'operation' => 'email.send',
                    'exact_destination' => 'https://api.agentmail.example/send',
                    'payload_digest' => str_repeat('c', 64),
                    'request_fingerprint' => str_repeat('d', 64),
                ],
                'assurance_profile' => $binding['assurance_profile'],
                'destination_policy' => $destinationPolicy,
                'scope' => [
                    'execution_id' => 'provider-execution-1',
                    'provider_id' => 'agentmail',
                    'adapter_id' => 'agentmail-email-transport',
                    'provider_substitution_permitted' => false,
                    'request_substitution_permitted' => false,
                ],
                'activation_authority_consumption' => [
                    'authority_id' => 'activation-issuance-authority-1',
                    'authority_digest' => str_repeat('e', 64),
                    'consumed_at' => $at->format(DATE_ATOM),
                    'consumed' => true,
                    'continuing_authority' => false,
                ],
                'status' => 'ACTIVATED_UNCONSUMED',
                'activated_at' => $at->format(DATE_ATOM),
                'expires_at' => $at->modify('+10 minutes')->format(DATE_ATOM),
                'single_operation' => true,
                'sealed' => true,
            ],
        );

        return [
            'execution_boundary' => $boundaryReference,
            'executor_principal' => $principalReference,
            'provider_binding_activation' => $this->recordReference(
                $activation,
                'activation_id',
            ),
            'provider_binding' => $bindingReference,
            'tool_authority' => $tool,
            'effect_authorization' => $effect,
            'request_reference' => $requestReference,
            'request' => [
                'request_id' => 'provider-request-1',
                'commission_id' => 'commission-1',
                'operation' => 'email.send',
                'exact_destination' => 'https://api.agentmail.example/send',
                'payload_digest' => str_repeat('c', 64),
                'request_fingerprint' => str_repeat('d', 64),
            ],
            'destination_policy' => $destinationPolicy,
            'assurance_profile' => $binding['assurance_profile'],
            'scope' => [
                'execution_id' => 'provider-execution-1',
                'provider_id' => 'agentmail',
                'adapter_id' => 'agentmail-email-transport',
                'credential_family' => 'agentmail-api-token',
                'provider_substitution_permitted' => false,
                'payload_substitution_permitted' => false,
                'destination_substitution_permitted' => false,
            ],
            'validity' => [
                'effective_at' => $at->format(DATE_ATOM),
                'expires_at' => $at->modify('+10 minutes')->format(DATE_ATOM),
                'revocation_reference' => null,
            ],
        ];
    }

    private function decision(array $candidate, \DateTimeImmutable $at): array
    {
        $contract = DurableProviderExecutionAuthorityIssuanceContract::class;
        $decisionId = 'durable-provider-execution-authority-decision-aaaaaaaaaaaaaaaaaaaa';
        $basis = [];
        foreach ($contract::REQUIRED_BASIS_FIELDS as $field) {
            $basis[$field] = 'request' === $field
                ? $candidate['request_reference']
                : $candidate[$field];
        }
        $candidateDigest = hash('sha256', CanonicalJson::encode($candidate));

        return $this->seal([
            'schema' => $contract::DECISION_SCHEMA,
            'decision_id' => $decisionId,
            'instance_id' => 'instance-1',
            'source_authority' => $this->reference('source-execution-authority-1'),
            'actor' => [
                'principal_id' => 'imperator-principal-1',
                'office' => 'imperator',
                'seat' => 'imperator',
                'binding_id' => 'imperator-binding-1',
                'generation' => 1,
            ],
            'target' => [
                'kind' => $contract::TARGET_KIND,
                'id' => 'durable-provider-execution-authority-1',
                'digest' => $candidateDigest,
                'schema' => DurableProviderExecutionAuthorityContract::SCHEMA,
            ],
            'basis' => $basis,
            'disposition' => 'AUTHORIZED',
            'rationale' => 'One exact durable execution authority may be issued.',
            'limitations' => 'Authority remains unconsumed; no credential or I/O.',
            'issuance_authority' => [
                'authority_id' => 'durable-provider-execution-authority-issuance-authority-1',
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'issuer_service' => 'imperator.durable-provider-execution-authority-issuer',
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

    private function boundary(\DateTimeImmutable $at): array
    {
        return [
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
            'executor_principal_requirements' => ['exact_principal_required' => true],
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
        ];
    }

    private function principal(
        array $boundaryReference,
        \DateTimeImmutable $at,
    ): array {
        return [
            'schema' => ProviderExecutorPrincipalContract::SCHEMA,
            'principal_attestation_id' => 'provider-executor-principal-attestation-1',
            'instance_id' => 'instance-1',
            'execution_boundary' => $boundaryReference,
            'principal' => [
                'principal_id' => 'provider-executor-principal-1',
                'infrastructure_role' => 'provider-executor',
                'binding_id' => 'provider-executor-binding-1',
                'generation' => 1,
                'process_boundary_id' => $boundaryReference['id'],
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
        ];
    }

    private function binding(\DateTimeImmutable $at): array
    {
        return [
            'schema' => ProviderImplementationBindingContract::SCHEMA,
            'binding_id' => 'provider-implementation-binding-1',
            'instance_id' => 'instance-1',
            'source_authority' => $this->reference('provider-binding-authority-1'),
            'tool_operation' => $this->reference('canonical-email-send.v1'),
            'provider_implementation' => [
                'provider_id' => 'agentmail',
                'adapter_id' => 'agentmail-email-transport',
                'adapter_version' => 'v1',
            ],
            'assurance_profile' => $this->reference('agentmail-assurance-profile-1'),
            'credential_family' => [
                'family_id' => 'agentmail-api-token',
                'provider_id' => 'agentmail',
                'secret_persistence_permitted' => false,
            ],
            'request_encoder' => $this->reference('agentmail-request-encoder-1'),
            'evidence_decoder' => $this->reference('agentmail-evidence-decoder-1'),
            'destination_policy' => [
                'policy_id' => 'email-destination-policy-1',
                'policy_digest' => str_repeat('b', 64),
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
        ];
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
