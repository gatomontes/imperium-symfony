<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class ProviderExecutionBoundaryContract
{
    public const string SCHEMA = 'imperium.la-cortine.provider-execution-boundary/v1';
    public const int VERSION = 1;
    public const string CANDIDATE_POSTURE = 'SAME_PROCESS_GOVERNED_EXECUTOR';
    public const string PRODUCER_POSTURE = 'deployment.provider-execution-boundary-definition';
    public const array CONSUMER_POSTURES = [
        'imperator.provider-executor-principal-attestation',
        'la-cortine.provider-execution-admission',
        'clavium.stationary-credential-resolution',
        'la-cortine.provider-execution-reconstruction',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'boundary_id', 'instance_id', 'deployment_boundary', 'authoritative_root',
        'credential_posture', 'executor_principal_requirements', 'admission_ordering',
        'threat_model', 'status', 'defined_at', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_DEPLOYMENT_BOUNDARY_FIELDS = [
        'boundary_kind', 'process_isolation_required', 'credential_possession_stationary',
        'cross_process_capability_transfer_required',
    ];
    public const array REQUIRED_CREDENTIAL_POSTURE_FIELDS = [
        'credential_owner', 'credential_reference_persistence_permitted',
        'credential_secret_persistence_permitted', 'credential_reconstruction_permitted',
    ];
    public const array REQUIRED_ADMISSION_ORDERING_FIELDS = [
        'authority_consumed_pre_resolution', 'effect_start_committed_pre_resolution',
        'effect_start_committed_pre_io', 'credential_resolution_inside_winning_boundary',
    ];
    public const array REQUIRED_THREAT_MODEL_FIELDS = [
        'integrity_posture', 'deployment_posture', 'hostile_writer_non_forgeability_claimed',
        'multi_host_consensus_claimed', 'split_brain_resistance_claimed',
    ];
    public const array STATUSES = ['DEFINED_INERT', 'RETIRED'];
    public const array SECRET_EXCLUSION = [
        'credential_reference_recorded' => false,
        'credential_secret_recorded' => false,
        'credential_material_logged' => false,
        'credential_material_in_exceptions' => false,
        'credential_material_reconstructed' => false,
    ];
    public const array NON_AUTHORITIES = [
        'installs_executor_principal' => false,
        'issues_execution_authority' => false,
        'activates_provider_binding' => false,
        'consumes_authority' => false,
        'issues_credential_capability' => false,
        'resolves_credentials' => false,
        'starts_effect' => false,
        'starts_external_io' => false,
        'opens_iron_gate' => false,
        'opens_lazaretto' => false,
        'grants_continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
