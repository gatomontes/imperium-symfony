<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class ProviderExecutorPrincipalActivationCanonicalResolutionAdmissionContract
{
    public const string SCHEMA =
        'imperium.la-cortine.provider-executor-principal-activation-canonical-resolution-admission/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE =
        'future-la-cortine-canonical-production-winner-resolution-admission';
    public const array CONSUMER_POSTURES = [
        'future-la-cortine-provider-executor-principal-canonical-activation-input',
    ];
    public const array REQUIRED_FIELDS = [
        'schema',
        'resolution_admission_id',
        'instance_id',
        'provenance_production',
        'production_decision',
        'principal_attestation',
        'provider_assurance_admission',
        'execution_boundary',
        'activation_target',
        'activation_authority',
        'replay_contention_root',
        'admitted_at',
        'exact_replay_only',
        'changed_evidence_conflicts',
        'resolution_required',
        'activation_performed',
        'authority_consumed',
        'continuing_authority',
        'sealed',
        'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_ACTIVATION_TARGET_FIELDS = [
        'principal_id',
        'binding_id',
        'generation',
        'process_boundary_id',
        'provider_id',
        'operation',
    ];
    public const array REQUIRED_ACTIVATION_AUTHORITY_FIELDS = [
        'authority_id',
        'decision_digest',
        'target_attestation_digest',
        'effective_at',
        'expires_at',
        'revocation_reference',
        'authority_single_use',
        'authority_exercisable',
        'consumed',
        'continuing_authority',
    ];
    public const array REQUIRED_REPLAY_CONTENTION_ROOT_FIELDS = [
        'root_id',
        'instance_id',
        'principal_id',
        'principal_generation',
        'process_boundary_id',
        'production_id',
        'decision_id',
        'authority_id',
    ];
    public const array NON_AUTHORITIES = [
        'resolves_live_custody' => false,
        'creates_resolution_admission' => false,
        'produces_activation_input' => false,
        'issues_activation_authority' => false,
        'consumes_activation_authority' => false,
        'activates_principal' => false,
        'activates_provider_binding' => false,
        'handles_credential_or_capability' => false,
        'invokes_provider' => false,
        'starts_effect' => false,
        'starts_external_io' => false,
        'authorizes_retry' => false,
        'migrates_live_consumer' => false,
        'opens_iron_gate' => false,
        'opens_lazaretto' => false,
        'grants_continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
