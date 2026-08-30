<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\ProviderExecutionBoundaryContract;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalContract;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\AuthorityConsumptionStore;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProviderExecutionBoundaryRedesignInertIssuanceService
{
    public const string BOUNDARY_DECISIONS = 'var/imperium/imperator/provider-execution-boundary-definition-decisions';
    public const string PRINCIPAL_DECISIONS = 'var/imperium/imperator/provider-executor-principal-attestation-decisions';
    public const string BOUNDARIES = 'var/imperium/offices/la-cortine/provider-execution-boundaries';
    public const string PRINCIPAL_ATTESTATIONS = 'var/imperium/offices/la-cortine/provider-executor-principal-attestations';
    public const string BOUNDARY_ISSUANCES = 'var/imperium/imperator/provider-execution-boundary-definition-issuances';
    public const string PRINCIPAL_ISSUANCES = 'var/imperium/imperator/provider-executor-principal-attestation-issuances';

    private AtomicTransition $atomic;
    private ImmutableRecordStore $records;
    private AuthorityConsumptionStore $consumptions;
    private RecordReferenceValidator $references;
    private ProviderExecutionBoundaryRedesignIssuanceContractValidator $contracts;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $this->atomic = new AtomicTransition($root);
        $this->records = new ImmutableRecordStore($root, $this->atomic);
        $this->consumptions = new AuthorityConsumptionStore($this->records, $this->atomic);
        $this->references = new RecordReferenceValidator($root);
        $this->contracts = new ProviderExecutionBoundaryRedesignIssuanceContractValidator();
    }

    public function defineBoundary(
        string $decisionId,
        array $definition,
        \DateTimeImmutable $at,
    ): array {
        $contract = ProviderExecutionBoundaryDefinitionIssuanceContract::class;
        $decision = $this->decision(self::BOUNDARY_DECISIONS, $decisionId, $contract, $at);
        $artifact = [
            'schema' => ProviderExecutionBoundaryContract::SCHEMA,
            'boundary_id' => $decision['target']['id'],
            'instance_id' => $decision['instance_id'],
            'deployment_boundary' => $definition['deployment_boundary'] ?? null,
            'authoritative_root' => $definition['authoritative_root'] ?? null,
            'credential_posture' => $definition['credential_posture'] ?? null,
            'executor_principal_requirements' => $definition['executor_principal_requirements'] ?? null,
            'admission_ordering' => $definition['admission_ordering'] ?? null,
            'threat_model' => $definition['threat_model'] ?? null,
            'status' => 'DEFINED_INERT',
            'defined_at' => $at->format(DATE_ATOM),
            'sealed' => true,
        ];
        $artifact['record_digest'] = hash('sha256', CanonicalJson::encode($artifact));
        $this->assertBoundary($artifact, $decision);

        return $this->issue(
            $decision,
            $artifact,
            $contract,
            self::BOUNDARIES,
            self::BOUNDARY_ISSUANCES,
            'boundary_defined',
            $at,
        );
    }

    public function attestPrincipal(
        string $decisionId,
        array $attestation,
        \DateTimeImmutable $at,
    ): array {
        $contract = ProviderExecutorPrincipalAttestationIssuanceContract::class;
        $decision = $this->decision(self::PRINCIPAL_DECISIONS, $decisionId, $contract, $at);
        $boundary = $this->references->resolve(
            $this->root.'/'.self::BOUNDARIES,
            $attestation['execution_boundary'] ?? [],
            'PEB320_EXECUTION_BOUNDARY_ABSENT',
            'PEB321_EXECUTION_BOUNDARY_MISMATCH',
            'boundary_id',
        );
        $this->assertStoredBoundary($boundary);
        $artifact = [
            'schema' => ProviderExecutorPrincipalContract::SCHEMA,
            'principal_attestation_id' => $decision['target']['id'],
            'instance_id' => $decision['instance_id'],
            'execution_boundary' => $attestation['execution_boundary'] ?? null,
            'principal' => $attestation['principal'] ?? null,
            'source_attestation' => $decision['source_authority'],
            'competence' => $attestation['competence'] ?? null,
            'validity' => $attestation['validity'] ?? null,
            'status' => 'ATTESTED_INERT',
            'attested_at' => $at->format(DATE_ATOM),
            'sealed' => true,
        ];
        $artifact['record_digest'] = hash('sha256', CanonicalJson::encode($artifact));
        $this->assertPrincipal($artifact, $decision, $boundary, $at);

        return $this->issue(
            $decision,
            $artifact,
            $contract,
            self::PRINCIPAL_ATTESTATIONS,
            self::PRINCIPAL_ISSUANCES,
            'principal_attestation_issued',
            $at,
        );
    }

    private function decision(
        string $directory,
        string $decisionId,
        string $contract,
        \DateTimeImmutable $at,
    ): array {
        if (!preg_match('/^[a-z0-9][a-z0-9._:-]{2,220}$/', $decisionId)) {
            throw new \InvalidArgumentException('PEB300_DECISION_ID_INVALID');
        }

        $decision = $this->references->read(
            $this->root.'/'.$directory.'/'.$decisionId.'.json',
            'PEB301_DECISION_ABSENT',
        );
        $this->contracts->assertDecision($decision, $contract, $at);
        if ($decisionId !== ($decision['decision_id'] ?? null)
            || 'AUTHORIZED' !== ($decision['disposition'] ?? null)) {
            throw new \RuntimeException('PEB302_DECISION_NOT_ISSUABLE');
        }

        return $decision;
    }

    private function issue(
        array $decision,
        array $artifact,
        string $contract,
        string $artifactDirectory,
        string $issuanceDirectory,
        string $resultField,
        \DateTimeImmutable $at,
    ): array {
        $authority = $decision['issuance_authority'];
        $artifactId = $decision['target']['id'];
        $issuanceId = str_replace('-decision-', '-issuance-', $decision['decision_id']);

        return $this->atomic->run(
            'provider-execution-inert-issuance:'.$authority['authority_id'],
            function () use (
                $decision,
                $artifact,
                $contract,
                $artifactDirectory,
                $issuanceDirectory,
                $resultField,
                $at,
                $authority,
                $artifactId,
                $issuanceId,
            ): array {
                $consumption = $this->consumptions->consume(
                    $authority['authority_id'],
                    $decision['decision_id'],
                    $decision['record_digest'],
                    self::class.'::'.$contract::TARGET_KIND,
                    $at,
                );
                $storedArtifact = $this->records->put(
                    $artifactDirectory,
                    $artifactId,
                    $artifact,
                );
                $issuance = [
                    'schema' => $contract::ISSUANCE_SCHEMA,
                    'issuance_id' => $issuanceId,
                    'instance_id' => $decision['instance_id'],
                    'source_decision' => [
                        'id' => $decision['decision_id'],
                        'digest' => $decision['record_digest'],
                        'schema' => $decision['schema'],
                    ],
                    'consumed_issuance_authority' => [
                        'id' => $authority['authority_id'],
                        'digest' => $decision['record_digest'],
                        'schema' => $decision['schema'],
                        'consumed_at' => $consumption['consumed_at'],
                        'consumed' => true,
                        'continuing_authority' => false,
                    ],
                    'issued_artifact' => [
                        'id' => $artifactId,
                        'digest' => $storedArtifact['record_digest'],
                        'schema' => $storedArtifact['schema'],
                    ],
                    'issuer' => $decision['actor'],
                    'issued_at' => $at->format(DATE_ATOM),
                    $resultField => true,
                    'principal_installed' => false,
                    'provider_binding_activated' => false,
                    'credential_capability_issued' => false,
                    'credential_resolved' => false,
                    'external_action_performed' => false,
                    'sealed' => true,
                ];
                $issuance['record_digest'] = hash('sha256', CanonicalJson::encode($issuance));
                $this->contracts->assertIssuance($issuance, $decision, $contract);

                return $this->records->put($issuanceDirectory, $issuanceId, $issuance);
            },
        );
    }

    private function assertBoundary(array $artifact, array $decision): void
    {
        if (ProviderExecutionBoundaryContract::REQUIRED_FIELDS !== array_keys($artifact)
            || ProviderExecutionBoundaryContract::SCHEMA !== $artifact['schema']
            || $decision['target'] !== [
                'kind' => ProviderExecutionBoundaryDefinitionIssuanceContract::TARGET_KIND,
                'id' => $artifact['boundary_id'],
                'digest' => $artifact['record_digest'],
                'schema' => $artifact['schema'],
            ]
            || $decision['instance_id'] !== $artifact['instance_id']
            || ProviderExecutionBoundaryContract::REQUIRED_DEPLOYMENT_BOUNDARY_FIELDS
                !== array_keys($artifact['deployment_boundary'] ?? [])
            || 'SAME_PROCESS_GOVERNED_EXECUTOR'
                !== ($artifact['deployment_boundary']['boundary_kind'] ?? null)
            || false !== ($artifact['deployment_boundary']['process_isolation_required'] ?? null)
            || true !== ($artifact['deployment_boundary']['credential_possession_stationary'] ?? null)
            || false !== ($artifact['deployment_boundary']['cross_process_capability_transfer_required'] ?? null)
            || ProviderExecutionBoundaryContract::REQUIRED_CREDENTIAL_POSTURE_FIELDS
                !== array_keys($artifact['credential_posture'] ?? [])
            || !is_string($artifact['credential_posture']['credential_owner'] ?? null)
            || '' === trim($artifact['credential_posture']['credential_owner'])
            || false !== ($artifact['credential_posture']['credential_reference_persistence_permitted'] ?? null)
            || false !== ($artifact['credential_posture']['credential_secret_persistence_permitted'] ?? null)
            || false !== ($artifact['credential_posture']['credential_reconstruction_permitted'] ?? null)
            || ProviderExecutionBoundaryContract::REQUIRED_ADMISSION_ORDERING_FIELDS
                !== array_keys($artifact['admission_ordering'] ?? [])
            || [true, true, true, true] !== array_values($artifact['admission_ordering'])
            || ProviderExecutionBoundaryContract::REQUIRED_THREAT_MODEL_FIELDS
                !== array_keys($artifact['threat_model'] ?? [])
            || 'TRUSTED_WRITER_CANONICAL_INTEGRITY'
                !== ($artifact['threat_model']['integrity_posture'] ?? null)
            || 'SINGLE_AUTHORITATIVE_ROOT_ONLY'
                !== ($artifact['threat_model']['deployment_posture'] ?? null)
            || false !== ($artifact['threat_model']['hostile_writer_non_forgeability_claimed'] ?? null)
            || false !== ($artifact['threat_model']['multi_host_consensus_claimed'] ?? null)
            || false !== ($artifact['threat_model']['split_brain_resistance_claimed'] ?? null)
            || !is_string($artifact['authoritative_root'])
            || '' === trim($artifact['authoritative_root'])
            || !is_array($artifact['executor_principal_requirements'])
            || [] === $artifact['executor_principal_requirements']) {
            throw new \RuntimeException('PEB310_BOUNDARY_DEFINITION_INVALID');
        }
    }

    private function assertStoredBoundary(array $boundary): void
    {
        $digest = $boundary['record_digest'] ?? null;
        $plain = $boundary;
        unset($plain['record_digest']);

        if (ProviderExecutionBoundaryContract::REQUIRED_FIELDS !== array_keys($boundary)
            || ProviderExecutionBoundaryContract::SCHEMA !== ($boundary['schema'] ?? null)
            || 'DEFINED_INERT' !== ($boundary['status'] ?? null)
            || true !== ($boundary['sealed'] ?? null)
            || !is_string($digest)
            || !hash_equals($digest, hash('sha256', CanonicalJson::encode($plain)))) {
            throw new \RuntimeException('PEB322_EXECUTION_BOUNDARY_INVALID');
        }
    }

    private function assertPrincipal(
        array $artifact,
        array $decision,
        array $boundary,
        \DateTimeImmutable $at,
    ): void {
        $validity = $artifact['validity'] ?? null;
        if (ProviderExecutorPrincipalContract::REQUIRED_FIELDS !== array_keys($artifact)
            || ProviderExecutorPrincipalContract::SCHEMA !== $artifact['schema']
            || $decision['target'] !== [
                'kind' => ProviderExecutorPrincipalAttestationIssuanceContract::TARGET_KIND,
                'id' => $artifact['principal_attestation_id'],
                'digest' => $artifact['record_digest'],
                'schema' => $artifact['schema'],
            ]
            || $decision['instance_id'] !== $artifact['instance_id']
            || $boundary['instance_id'] !== $artifact['instance_id']
            || ($decision['basis']['execution_boundary'] ?? null)
                !== $artifact['execution_boundary']
            || ProviderExecutorPrincipalContract::REQUIRED_REFERENCE_FIELDS
                !== array_keys($artifact['execution_boundary'] ?? [])
            || ProviderExecutorPrincipalContract::REQUIRED_PRINCIPAL_FIELDS
                !== array_keys($artifact['principal'] ?? [])
            || !is_string($artifact['principal']['principal_id'] ?? null)
            || '' === trim($artifact['principal']['principal_id'])
            || !is_string($artifact['principal']['infrastructure_role'] ?? null)
            || '' === trim($artifact['principal']['infrastructure_role'])
            || !is_string($artifact['principal']['binding_id'] ?? null)
            || '' === trim($artifact['principal']['binding_id'])
            || !is_int($artifact['principal']['generation'] ?? null)
            || ($artifact['principal']['generation'] ?? 0) < 1
            || $boundary['boundary_id'] !== ($artifact['principal']['process_boundary_id'] ?? null)
            || ProviderExecutorPrincipalContract::REQUIRED_COMPETENCE_FIELDS
                !== array_keys($artifact['competence'] ?? [])
            || !is_string($artifact['competence']['operation'] ?? null)
            || !is_string($artifact['competence']['provider_id'] ?? null)
            || !is_string($artifact['competence']['adapter_id'] ?? null)
            || !is_string($artifact['competence']['credential_family'] ?? null)
            || true !== ($artifact['competence']['same_process_execution_required'] ?? null)
            || ProviderExecutorPrincipalContract::REQUIRED_VALIDITY_FIELDS
                !== array_keys($validity ?? [])
            || !$this->activeWindow($validity, $at, $decision['expires_at'])
            || null !== ($validity['revocation_reference'] ?? null)) {
            throw new \RuntimeException('PEB323_EXECUTOR_PRINCIPAL_ATTESTATION_INVALID');
        }
    }

    private function activeWindow(
        array $validity,
        \DateTimeImmutable $at,
        string $decisionExpiresAt,
    ): bool {
        try {
            $effective = new \DateTimeImmutable((string) ($validity['effective_at'] ?? ''));
            $expires = new \DateTimeImmutable((string) ($validity['expires_at'] ?? ''));
            $decisionExpiry = new \DateTimeImmutable($decisionExpiresAt);
        } catch (\Exception) {
            return false;
        }

        return $effective <= $at && $at < $expires && $expires <= $decisionExpiry;
    }
}
