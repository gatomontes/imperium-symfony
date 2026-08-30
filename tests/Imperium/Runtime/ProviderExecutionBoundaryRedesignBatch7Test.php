<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Clavium\GovernedStationaryCredentialResolutionService;
use App\Imperium\Runtime\Clavium\StationaryCredentialResolutionProofContract;
use App\Imperium\Runtime\Imperator\DurableProviderExecutionAuthorityContract;
use App\Imperium\Runtime\Imperator\DurableProviderExecutionAuthorityIssuanceService;
use App\Imperium\Runtime\Imperator\ProviderExecutionBoundaryRedesignInertIssuanceService;
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionAdmissionContract;
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionAdmissionService;
use App\Imperium\Runtime\LaCortine\ProviderExecutionBoundaryContract;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalContract;
use App\Imperium\Runtime\LaCortine\ProviderImplementationBindingContract;
use App\Imperium\Runtime\LaCortine\ProviderImplementationBindingService;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use PHPUnit\Framework\TestCase;

final class ProviderExecutionBoundaryRedesignBatch7Test extends TestCase
{
    private string $root;
    private ImmutableRecordStore $records;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-peb-b7-'.bin2hex(random_bytes(6));
        $atomic = new AtomicTransition($this->root);
        $this->records = new ImmutableRecordStore($this->root, $atomic);
        $_ENV['AGENTMAIL_API_KEY'] = 'test-secret-never-persisted';
        $_SERVER['AGENTMAIL_API_KEY'] = 'test-secret-never-persisted';
    }

    protected function tearDown(): void
    {
        unset($_ENV['AGENTMAIL_API_KEY'], $_SERVER['AGENTMAIL_API_KEY']);
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

    public function testCredentialResolvesOnlyInsideCallbackAndProofExcludesSecret(): void
    {
        $at = new \DateTimeImmutable('2026-08-30T19:00:00+00:00');
        $admission = $this->seedLineage($at);
        $service = new GovernedStationaryCredentialResolutionService($this->root);

        $proof = $service->prove($admission['admission_id'], $at);
        unset($_ENV['AGENTMAIL_API_KEY'], $_SERVER['AGENTMAIL_API_KEY']);
        $reconstructed = $service->prove(
            $admission['admission_id'],
            $at->modify('+20 minutes'),
        );

        self::assertSame($proof, $reconstructed);
        self::assertSame(
            StationaryCredentialResolutionProofContract::CHECKPOINT,
            $proof['resolution']['checkpoint'],
        );
        self::assertTrue($proof['resolution']['credential_resolved']);
        self::assertTrue($proof['resolution']['callback_local']);
        self::assertFalse($proof['resolution']['secret_exposed_to_caller']);
        self::assertFalse($proof['resolution']['credential_reference_persisted']);
        self::assertFalse($proof['resolution']['credential_secret_persisted']);
        self::assertFalse($proof['resolution']['credential_capability_issued']);
        self::assertFalse($proof['resolution']['credential_capability_reconstructed']);
        foreach ($proof['effect'] as $effect) {
            self::assertFalse($effect);
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->root.'/var/imperium',
                \FilesystemIterator::SKIP_DOTS,
            ),
        );
        foreach ($files as $file) {
            if (!$file->isFile() || 'json' !== $file->getExtension()) {
                continue;
            }
            $contents = (string) file_get_contents($file->getPathname());
            self::assertStringNotContainsString('test-secret-never-persisted', $contents);
            self::assertStringNotContainsString('AGENTMAIL_API_KEY', $contents);
        }
        foreach (StationaryCredentialResolutionProofContract::NON_AUTHORITIES as $permission) {
            self::assertFalse($permission);
        }
    }

    public function testMissingStationaryCredentialRefusesWithoutProof(): void
    {
        $at = new \DateTimeImmutable('2026-08-30T19:00:00+00:00');
        $admission = $this->seedLineage($at);
        unset($_ENV['AGENTMAIL_API_KEY'], $_SERVER['AGENTMAIL_API_KEY']);

        $this->expectExceptionMessage('PEB711_STATIONARY_CREDENTIAL_UNAVAILABLE');
        (new GovernedStationaryCredentialResolutionService($this->root))
            ->prove($admission['admission_id'], $at);
    }

    public function testSourceHasNoCapabilityProviderOrExternalIoPath(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3)
            .'/src/Imperium/Runtime/Clavium/'
            .'GovernedStationaryCredentialResolutionService.php',
        );

        foreach ([
            "'credential_resolved' => true",
            "'callback_local' => true",
            "'secret_exposed_to_caller' => false",
            "'credential_reference_persisted' => false",
            "'credential_secret_persisted' => false",
            "'credential_capability_issued' => false",
            "'provider_invoked' => false",
            "'external_io_started' => false",
            "'outbound_byte_sent' => false",
        ] as $proof) {
            self::assertStringContainsString($proof, $source);
        }
        foreach ([
            'CredentialCapability',
            'CredentialBroker',
            'DeterministicTransport',
            'AgentMailEmailTransport',
            'IronGate',
            'Lazaretto',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testBatchDocumentationAuthorizesOnlyFailureProofNext(): void
    {
        $handoff = preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(
                dirname(__DIR__, 3)
                .'/docs/handoffs/provider-execution-boundary-redesign-batch-7-complete.md',
            ),
        );

        foreach ([
            'Only Batch 8 may next be considered',
            'crash, replay, contention, expiry, revocation and secret-exclusion proof',
            'may not invoke a provider',
            'external I/O',
            'Iron Gate',
            'Lazaretto',
            'Provider Execution Assurance remains paused',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }

    private function seedLineage(\DateTimeImmutable $at): array
    {
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
        $authority = $this->records->put(
            DurableProviderExecutionAuthorityIssuanceService::AUTHORITIES,
            'durable-provider-execution-authority-1',
            $this->authority($boundaryRef, $principalRef, $bindingRef, $binding, $at),
        );
        $authorityRef = $this->ref($authority, 'authority_id');
        $admissionId = 'governed-provider-execution-admission-'.substr(
            hash('sha256', $authority['authority_id'].'|'.$authority['record_digest']),
            0,
            20,
        );

        return $this->records->put(
            GovernedProviderExecutionAdmissionService::ADMISSIONS,
            $admissionId,
            [
                'schema' => GovernedProviderExecutionAdmissionContract::SCHEMA,
                'admission_id' => $admissionId,
                'instance_id' => 'instance-1',
                'execution_boundary' => $boundaryRef,
                'executor_principal' => $principalRef,
                'provider_binding_activation' => $authority['provider_binding_activation'],
                'provider_binding' => $bindingRef,
                'execution_authority' => $authorityRef,
                'request' => $authority['request'],
                'authority_consumption' => [
                    'authority_id' => $authority['authority_id'],
                    'authority_digest' => $authority['record_digest'],
                    'single_use' => true,
                    'consumed' => true,
                    'continuing_authority' => false,
                    'winner_scope' => 'single-authoritative-root:'.$authority['authority_id'],
                ],
                'effect_start' => [
                    'checkpoint' => GovernedProviderExecutionAdmissionContract::CHECKPOINT,
                    'local_effect_start_committed' => true,
                    'credential_resolution_permitted_after_checkpoint' => true,
                    'credential_resolved' => false,
                    'external_io_started' => false,
                    'provider_invoked' => false,
                    'automatic_replay_permitted' => false,
                    'exact_admission_continuation_permitted' => true,
                    'outcome' => 'NOT_ATTEMPTED',
                ],
                'status' => GovernedProviderExecutionAdmissionContract::STATUS,
                'admitted_at' => $at->format(DATE_ATOM),
                'expires_at' => $at->modify('+10 minutes')->format(DATE_ATOM),
                'sealed' => true,
            ],
        );
    }

    private function authority(
        array $boundaryRef,
        array $principalRef,
        array $bindingRef,
        array $binding,
        \DateTimeImmutable $at,
    ): array {
        return [
            'schema' => DurableProviderExecutionAuthorityContract::SCHEMA,
            'authority_id' => 'durable-provider-execution-authority-1',
            'instance_id' => 'instance-1',
            'source_decision' => $this->reference('execution-authority-decision-1'),
            'execution_boundary' => $boundaryRef,
            'executor_principal' => $principalRef,
            'tool_authority' => $binding['tool_operation'],
            'effect_authorization' => $this->reference('effect-authorization-1'),
            'provider_binding_activation' => $this->reference('provider-activation-1'),
            'provider_binding' => $bindingRef,
            'request' => [
                'request_id' => 'provider-request-1',
                'commission_id' => 'commission-1',
                'operation' => 'email.send',
                'exact_destination' => 'https://api.agentmail.example/send',
                'payload_digest' => str_repeat('c', 64),
                'request_fingerprint' => str_repeat('d', 64),
            ],
            'destination_policy' => [
                'id' => $binding['destination_policy']['policy_id'],
                'digest' => $binding['destination_policy']['policy_digest'],
                'schema' => 'imperium.la-cortine.destination-policy/v1',
            ],
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
            'authority_single_use' => true,
            'authority_exercisable' => true,
            'consumed' => false,
            'continuing_authority' => false,
            'issued_at' => $at->format(DATE_ATOM),
            'sealed' => true,
        ];
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
