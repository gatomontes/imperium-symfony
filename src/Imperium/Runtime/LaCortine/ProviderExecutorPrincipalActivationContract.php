<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class ProviderExecutorPrincipalActivationContract
{
    public const string SCHEMA = 'imperium.la-cortine.provider-executor-principal-activation/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'future-la-cortine-provider-executor-principal-activation-transition';
    public const array CONSUMER_POSTURES = [
        'future-la-cortine-operation-binding-live-sufficiency-assessment',
        'future-la-cortine-provider-live-call-contract-validation',
        'future-la-cortine-first-byte-gate',
        'la-cortine.provider-execution-reconstruction',
    ];
    public const array REQUIRED_FIELDS = [
        'schema',
        'principal_activation_id',
        'instance_id',
        'source_decision',
        'consumed_activation_authority',
        'provider_assurance_admission',
        'execution_boundary',
        'principal_attestation',
        'principal',
        'scope',
        'validity',
        'reconstruction',
        'status',
        'activated_at',
        'sealed',
        'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_CONSUMED_AUTHORITY_FIELDS = [
        'id',
        'digest',
        'schema',
        'consumed_at',
        'consumed',
        'continuing_authority',
    ];
    public const array REQUIRED_PRINCIPAL_FIELDS = [
        'principal_id',
        'infrastructure_role',
        'binding_id',
        'generation',
        'process_boundary_id',
    ];
    public const array REQUIRED_SCOPE_FIELDS = [
        'provider_id',
        'operation',
        'same_process_execution_required',
        'provider_substitution_permitted',
        'operation_substitution_permitted',
        'principal_generation_substitution_permitted',
    ];
    public const array REQUIRED_VALIDITY_FIELDS = [
        'effective_at',
        'expires_at',
        'revocation_reference',
    ];
    public const array REQUIRED_RECONSTRUCTION_FIELDS = [
        'read_only',
        'exact_replay_only',
        'reactivation_permitted',
        'generation_upgrade_permitted',
    ];
    public const array STATUSES = [
        'ACTIVE',
        'EXPIRED',
        'REVOKED',
    ];
    public const string UNKNOWN_OUTCOME_POSTURE = 'UNKNOWN_REPLAY_PROHIBITED';
    public const array NON_AUTHORITIES = [
        'produces_activation_record' => false,
        'activates_principal' => false,
        'reactivates_principal' => false,
        'upgrades_principal_generation' => false,
        'mutates_principal_attestation' => false,
        'activates_provider_binding' => false,
        'defines_live_call_runtime' => false,
        'issues_execution_authority' => false,
        'consumes_execution_authority' => false,
        'issues_credential_capability' => false,
        'resolves_credentials' => false,
        'starts_effect' => false,
        'starts_external_io' => false,
        'authorizes_retry' => false,
        'opens_iron_gate' => false,
        'opens_lazaretto' => false,
        'grants_continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
