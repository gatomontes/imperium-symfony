<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ProviderExecutionBoundaryDefinitionIssuanceContract;
use App\Imperium\Runtime\Imperator\ProviderExecutionBoundaryRedesignInertIssuanceService;
use App\Imperium\Runtime\Imperator\ProviderExecutorPrincipalAttestationIssuanceContract;
use App\Imperium\Runtime\LaCortine\ProviderExecutionBoundaryContract;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalContract;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use PHPUnit\Framework\TestCase;

final class ProviderExecutionBoundaryRedesignBatch3Test extends TestCase
{
    private string $root;
    private ImmutableRecordStore $records;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-peb-b3-'.bin2hex(random_bytes(6));
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

    public function testBoundaryDefinitionConsumesExactAuthorityAndRemainsInert(): void
    {
        $at = new \DateTimeImmutable('2026-08-30T15:00:00+00:00');
        $definition = $this->boundaryDefinition();
        $artifact = $this->boundaryArtifact($definition, $at);
        $decision = $this->decision(
            ProviderExecutionBoundaryDefinitionIssuanceContract::class,
            'provider-execution-boundary-definition-decision-aaaaaaaaaaaaaaaaaaaa',
            $artifact,
            $at,
        );
        $this->records->put(
            ProviderExecutionBoundaryRedesignInertIssuanceService::BOUNDARY_DECISIONS,
            $decision['decision_id'],
            $decision,
        );

        $service = new ProviderExecutionBoundaryRedesignInertIssuanceService($this->root);
        $issuance = $service->defineBoundary($decision['decision_id'], $definition, $at);
        $replay = $service->defineBoundary($decision['decision_id'], $definition, $at);

        self::assertSame($issuance, $replay);
        self::assertTrue($issuance['boundary_defined']);
        self::assertFalse($issuance['principal_installed']);
        self::assertFalse($issuance['provider_binding_activated']);
        self::assertFalse($issuance['credential_capability_issued']);
        self::assertFalse($issuance['credential_resolved']);
        self::assertFalse($issuance['external_action_performed']);

        $stored = $this->records->read(
            ProviderExecutionBoundaryRedesignInertIssuanceService::BOUNDARIES,
            $artifact['boundary_id'],
        );
        self::assertSame('DEFINED_INERT', $stored['status']);
        self::assertSame('SAME_PROCESS_GOVERNED_EXECUTOR', $stored['deployment_boundary']['boundary_kind']);
        self::assertTrue($stored['credential_posture']['credential_owner'] === 'deployment.environment');
        foreach (ProviderExecutionBoundaryContract::NON_AUTHORITIES as $permission) {
            self::assertFalse($permission);
        }
    }

    public function testPrincipalAttestationBindsExactBoundaryAndInstallsNothing(): void
    {
        $at = new \DateTimeImmutable('2026-08-30T15:00:00+00:00');
        $service = new ProviderExecutionBoundaryRedesignInertIssuanceService($this->root);
        $definition = $this->boundaryDefinition();
        $boundary = $this->boundaryArtifact($definition, $at);
        $boundaryDecision = $this->decision(
            ProviderExecutionBoundaryDefinitionIssuanceContract::class,
            'provider-execution-boundary-definition-decision-bbbbbbbbbbbbbbbbbbbb',
            $boundary,
            $at,
        );
        $this->records->put(
            ProviderExecutionBoundaryRedesignInertIssuanceService::BOUNDARY_DECISIONS,
            $boundaryDecision['decision_id'],
            $boundaryDecision,
        );
        $service->defineBoundary($boundaryDecision['decision_id'], $definition, $at);
        $storedBoundary = $this->records->read(
            ProviderExecutionBoundaryRedesignInertIssuanceService::BOUNDARIES,
            $boundary['boundary_id'],
        );
        $boundaryReference = [
            'id' => $storedBoundary['boundary_id'],
            'digest' => $storedBoundary['record_digest'],
            'schema' => $storedBoundary['schema'],
        ];
        $attestation = $this->principalAttestation($boundaryReference, $at);
        $principalArtifact = $this->principalArtifact(
            $attestation,
            'provider-executor-principal-attestation-decision-cccccccccccccccccccc',
            $at,
        );
        $principalDecision = $this->decision(
            ProviderExecutorPrincipalAttestationIssuanceContract::class,
            'provider-executor-principal-attestation-decision-cccccccccccccccccccc',
            $principalArtifact,
            $at,
            ['execution_boundary' => $boundaryReference],
        );
        $this->records->put(
            ProviderExecutionBoundaryRedesignInertIssuanceService::PRINCIPAL_DECISIONS,
            $principalDecision['decision_id'],
            $principalDecision,
        );

        $issuance = $service->attestPrincipal(
            $principalDecision['decision_id'],
            $attestation,
            $at,
        );

        self::assertTrue($issuance['principal_attestation_issued']);
        self::assertFalse($issuance['principal_installed']);
        self::assertFalse($issuance['provider_binding_activated']);
        self::assertFalse($issuance['credential_capability_issued']);
        self::assertFalse($issuance['credential_resolved']);
        self::assertFalse($issuance['external_action_performed']);

        $stored = $this->records->read(
            ProviderExecutionBoundaryRedesignInertIssuanceService::PRINCIPAL_ATTESTATIONS,
            $principalArtifact['principal_attestation_id'],
        );
        self::assertSame('ATTESTED_INERT', $stored['status']);
        self::assertSame($boundaryReference, $stored['execution_boundary']);
        self::assertTrue($stored['competence']['same_process_execution_required']);
        foreach (ProviderExecutorPrincipalContract::NON_AUTHORITIES as $permission) {
            self::assertFalse($permission);
        }
    }

    public function testSourceContainsNoCredentialOrProviderExecutionDependency(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3)
            .'/src/Imperium/Runtime/Imperator/'
            .'ProviderExecutionBoundaryRedesignInertIssuanceService.php',
        );

        foreach ([
            "'status' => 'DEFINED_INERT'",
            "'status' => 'ATTESTED_INERT'",
            "'principal_installed' => false",
            "'provider_binding_activated' => false",
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
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testBatchDocumentationAuthorizesOnlyInertActivationNext(): void
    {
        $root = dirname(__DIR__, 3);
        $handoff = preg_replace(
            '/\\s+/',
            ' ',
            (string) file_get_contents(
                $root.'/docs/handoffs/provider-execution-boundary-redesign-batch-3-complete.md',
            ),
        );

        foreach ([
            'Only Batch 4 may next be considered',
            'ACTIVATED_UNCONSUMED',
            'may not issue or consume durable provider-execution authority',
            'handle a credential or capability',
            'external I/O',
            'Iron Gate',
            'Lazaretto',
            'Provider Execution Assurance remains paused',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }

    private function boundaryDefinition(): array
    {
        return [
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
                'current_generation_required' => true,
                'same_process_required' => true,
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
        ];
    }

    private function boundaryArtifact(array $definition, \DateTimeImmutable $at): array
    {
        return $this->seal([
            'schema' => ProviderExecutionBoundaryContract::SCHEMA,
            'boundary_id' => 'provider-execution-boundary-1',
            'instance_id' => 'instance-1',
            ...$definition,
            'status' => 'DEFINED_INERT',
            'defined_at' => $at->format(DATE_ATOM),
            'sealed' => true,
        ]);
    }

    private function principalAttestation(
        array $boundaryReference,
        \DateTimeImmutable $at,
    ): array {
        return [
            'execution_boundary' => $boundaryReference,
            'principal' => [
                'principal_id' => 'provider-executor-principal-1',
                'infrastructure_role' => 'provider-executor',
                'binding_id' => 'provider-executor-binding-1',
                'generation' => 1,
                'process_boundary_id' => $boundaryReference['id'],
            ],
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
        ];
    }

    private function principalArtifact(
        array $attestation,
        string $decisionId,
        \DateTimeImmutable $at,
    ): array {
        return $this->seal([
            'schema' => ProviderExecutorPrincipalContract::SCHEMA,
            'principal_attestation_id' => 'provider-executor-principal-attestation-1',
            'instance_id' => 'instance-1',
            'execution_boundary' => $attestation['execution_boundary'],
            'principal' => $attestation['principal'],
            'source_attestation' => $this->reference('source-authority-1'),
            'competence' => $attestation['competence'],
            'validity' => $attestation['validity'],
            'status' => 'ATTESTED_INERT',
            'attested_at' => $at->format(DATE_ATOM),
            'sealed' => true,
        ]);
    }

    /**
     * @param class-string $contract
     */
    private function decision(
        string $contract,
        string $decisionId,
        array $artifact,
        \DateTimeImmutable $at,
        array $basisOverrides = [],
    ): array {
        $basis = [];
        foreach ($contract::REQUIRED_BASIS_FIELDS as $field) {
            $basis[$field] = $basisOverrides[$field]
                ?? $this->reference(str_replace('_', '-', $field).'-1');
        }

        return $this->seal([
            'schema' => $contract::DECISION_SCHEMA,
            'decision_id' => $decisionId,
            'instance_id' => 'instance-1',
            'source_authority' => $this->reference('source-authority-1'),
            'actor' => [
                'principal_id' => 'imperator-principal-1',
                'office' => 'imperator',
                'seat' => 'imperator',
                'binding_id' => 'imperator-binding-1',
                'generation' => 1,
            ],
            'target' => [
                'kind' => $contract::TARGET_KIND,
                'id' => $artifact[array_keys($artifact)[1]],
                'digest' => $artifact['record_digest'],
                'schema' => $artifact['schema'],
            ],
            'basis' => $basis,
            'disposition' => 'AUTHORIZED',
            'rationale' => 'Exact inert artifact may be issued.',
            'limitations' => 'No execution or credential authority.',
            'issuance_authority' => [
                'authority_id' => str_replace(
                    '-decision-',
                    '-issuance-authority-',
                    $decisionId,
                ),
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'issuer_service' => 'imperator.inert-provider-execution-artifact-issuer',
                'permitted_transition' => $contract::PERMITTED_TRANSITION,
                'target_digest' => $artifact['record_digest'],
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
