<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\DurableProviderExecutionAuthorityContract;
use App\Imperium\Runtime\Imperator\DurableProviderExecutionAuthorityIssuanceService;
use App\Imperium\Runtime\Imperator\ProviderExecutionBoundaryRedesignInertIssuanceService;
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionAdmissionContract;
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionAdmissionService;
use App\Imperium\Runtime\LaCortine\ProviderExecutionBoundaryContract;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalContract;
use App\Imperium\Runtime\LaCortine\ProviderImplementationBindingContract;
use App\Imperium\Runtime\LaCortine\ProviderImplementationBindingService;
use App\Imperium\Runtime\LaCortine\SingleOperationProviderBindingActivationContract;
use App\Imperium\Runtime\LaCortine\SingleOperationProviderBindingActivationIssuanceService;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use PHPUnit\Framework\TestCase;

final class ProviderExecutionBoundaryRedesignBatch6Test extends TestCase
{
    private string $root;
    private ImmutableRecordStore $records;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-peb-b6-'.bin2hex(random_bytes(6));
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

    public function testAuthorityConsumptionAndEffectStartAreOneImmutableWinner(): void
    {
        $at = new \DateTimeImmutable('2026-08-30T18:00:00+00:00');
        $authority = $this->seedLineage($at, $at->modify('+10 minutes'));
        $service = new GovernedProviderExecutionAdmissionService($this->root);

        $admission = $service->admit($authority['authority_id'], $at);
        $reconstructed = $service->admit(
            $authority['authority_id'],
            $at->modify('+20 minutes'),
        );

        self::assertSame($admission, $reconstructed);
        self::assertSame(
            GovernedProviderExecutionAdmissionContract::STATUS,
            $admission['status'],
        );
        self::assertTrue($admission['authority_consumption']['consumed']);
        self::assertTrue($admission['authority_consumption']['single_use']);
        self::assertFalse($admission['authority_consumption']['continuing_authority']);
        self::assertSame(
            GovernedProviderExecutionAdmissionContract::CHECKPOINT,
            $admission['effect_start']['checkpoint'],
        );
        self::assertTrue($admission['effect_start']['local_effect_start_committed']);
        self::assertTrue(
            $admission['effect_start']['credential_resolution_permitted_after_checkpoint'],
        );
        self::assertFalse($admission['effect_start']['credential_resolved']);
        self::assertFalse($admission['effect_start']['external_io_started']);
        self::assertFalse($admission['effect_start']['provider_invoked']);
        self::assertFalse($admission['effect_start']['automatic_replay_permitted']);
        self::assertTrue(
            $admission['effect_start']['exact_admission_continuation_permitted'],
        );
        self::assertSame('NOT_ATTEMPTED', $admission['effect_start']['outcome']);

        foreach (GovernedProviderExecutionAdmissionContract::NON_AUTHORITIES as $permission) {
            self::assertFalse($permission);
        }
    }

    public function testExpiredAuthorityCannotCreateFirstAdmissionWinner(): void
    {
        $at = new \DateTimeImmutable('2026-08-30T18:00:00+00:00');
        $authority = $this->seedLineage($at, $at->modify('+1 minute'));

        $this->expectExceptionMessage('PEB602_EXECUTION_AUTHORITY_INVALID');
        (new GovernedProviderExecutionAdmissionService($this->root))
            ->admit($authority['authority_id'], $at->modify('+2 minutes'));
    }

    public function testSourceHasOneCombinedWinnerAndNoCredentialOrProviderPath(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3)
            .'/src/Imperium/Runtime/LaCortine/'
            .'GovernedProviderExecutionAdmissionService.php',
        );

        foreach ([
            "'authority_consumption' => [",
            "'consumed' => true",
            "'effect_start' => [",
            "'local_effect_start_committed' => true",
            "'credential_resolved' => false",
            "'external_io_started' => false",
            "'provider_invoked' => false",
            "'automatic_replay_permitted' => false",
        ] as $proof) {
            self::assertStringContainsString($proof, $source);
        }
        foreach ([
            'AuthorityConsumptionStore',
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

    public function testBatchDocumentationAuthorizesOnlyStationaryResolutionNext(): void
    {
        $handoff = preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(
                dirname(__DIR__, 3)
                .'/docs/handoffs/provider-execution-boundary-redesign-batch-6-complete.md',
            ),
        );

        foreach ([
            'Only Batch 7 may next be considered',
            'same-process stationary credential resolution',
            'exact admission winner',
            'may not invoke a provider',
            'external I/O',
            'Iron Gate',
            'Lazaretto',
            'Provider Execution Assurance remains paused',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }

    private function seedLineage(
        \DateTimeImmutable $at,
        \DateTimeImmutable $expiresAt,
    ): array {
        $boundary = $this->records->put(
            ProviderExecutionBoundaryRedesignInertIssuanceService::BOUNDARIES,
            'provider-execution-boundary-1',
            $this->boundary($at),
        );
        $boundaryRef = $this->ref($boundary, 'boundary_id');
        $principal = $this->records->put(
            ProviderExecutionBoundaryRedesignInertIssuanceService::PRINCIPAL_ATTESTATIONS,
            'provider-executor-principal-attestation-1',
            $this->principal($boundaryRef, $at),
        );
        $principalRef = $this->ref($principal, 'principal_attestation_id');
        $binding = $this->records->put(
            ProviderImplementationBindingService::BINDINGS,
            'provider-implementation-binding-1',
            $this->binding($at),
        );
        $bindingRef = $this->ref($binding, 'binding_id');
        $effect = $this->reference('effect-authorization-1');
        $destinationPolicy = [
            'id' => $binding['destination_policy']['policy_id'],
            'digest' => $binding['destination_policy']['policy_digest'],
            'schema' => 'imperium.la-cortine.destination-policy/v1',
        ];
        $request = [
            'request_id' => 'provider-request-1',
            'operation' => 'email.send',
            'exact_destination' => 'https://api.agentmail.example/send',
            'payload_digest' => str_repeat('c', 64),
            'request_fingerprint' => str_repeat('d', 64),
        ];
        $activation = $this->records->put(
            SingleOperationProviderBindingActivationIssuanceService::ACTIVATIONS,
            'single-operation-provider-binding-activation-1',
            [
                'schema' => SingleOperationProviderBindingActivationContract::SCHEMA,
                'activation_id' => 'single-operation-provider-binding-activation-1',
                'instance_id' => 'instance-1',
                'source_activation_authority' => $this->reference('activation-authority-1'),
                'execution_boundary' => $boundaryRef,
                'executor_principal' => $principalRef,
                'provider_binding' => $bindingRef,
                'tool_authority' => $binding['tool_operation'],
                'effect_authorization' => $effect,
                'request' => $request,
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
                    'authority_id' => 'activation-authority-1',
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
        $activationRef = $this->ref($activation, 'activation_id');
        $authority = [
            'schema' => DurableProviderExecutionAuthorityContract::SCHEMA,
            'authority_id' => 'durable-provider-execution-authority-1',
            'instance_id' => 'instance-1',
            'source_decision' => $this->reference('execution-authority-decision-1'),
            'execution_boundary' => $boundaryRef,
            'executor_principal' => $principalRef,
            'tool_authority' => $binding['tool_operation'],
            'effect_authorization' => $effect,
            'provider_binding_activation' => $activationRef,
            'provider_binding' => $bindingRef,
            'request' => [
                'request_id' => $request['request_id'],
                'commission_id' => 'commission-1',
                'operation' => $request['operation'],
                'exact_destination' => $request['exact_destination'],
                'payload_digest' => $request['payload_digest'],
                'request_fingerprint' => $request['request_fingerprint'],
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
                'expires_at' => $expiresAt->format(DATE_ATOM),
                'revocation_reference' => null,
            ],
            'authority_single_use' => true,
            'authority_exercisable' => true,
            'consumed' => false,
            'continuing_authority' => false,
            'issued_at' => $at->format(DATE_ATOM),
            'sealed' => true,
        ];

        return $this->records->put(
            DurableProviderExecutionAuthorityIssuanceService::AUTHORITIES,
            $authority['authority_id'],
            $authority,
        );
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
        array $boundaryRef,
        \DateTimeImmutable $at,
    ): array {
        return [
            'schema' => ProviderExecutorPrincipalContract::SCHEMA,
            'principal_attestation_id' => 'provider-executor-principal-attestation-1',
            'instance_id' => 'instance-1',
            'execution_boundary' => $boundaryRef,
            'principal' => [
                'principal_id' => 'provider-executor-principal-1',
                'infrastructure_role' => 'provider-executor',
                'binding_id' => 'provider-executor-binding-1',
                'generation' => 1,
                'process_boundary_id' => $boundaryRef['id'],
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

    private function ref(array $record, string $idField): array
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
}
