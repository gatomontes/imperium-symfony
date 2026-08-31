<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class ProviderExecutorPrincipalActivationCanonicalInputContract
{
    public const string SCHEMA =
        'imperium.la-cortine.provider-executor-principal-activation-canonical-input/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE =
        'future-la-cortine-canonical-production-winner-resolution-admission';
    public const array CONSUMER_POSTURES = [
        'future-la-cortine-provider-executor-principal-activation-transition',
    ];
    public const array REQUIRED_FIELDS = [
        'schema',
        'input_id',
        'instance_id',
        'resolution_admission',
        'provenance_production',
        'production_decision',
        'principal_attestation',
        'provider_assurance_admission',
        'execution_boundary',
        'activation_target',
        'activation_authority',
        'replay_contention_root',
        'exact_replay_only',
        'changed_evidence_conflicts',
        'sealed',
        'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS =
        ProviderExecutorPrincipalActivationCanonicalResolutionAdmissionContract::REQUIRED_REFERENCE_FIELDS;
    public const array REQUIRED_ACTIVATION_TARGET_FIELDS =
        ProviderExecutorPrincipalActivationCanonicalResolutionAdmissionContract::REQUIRED_ACTIVATION_TARGET_FIELDS;
    public const array REQUIRED_ACTIVATION_AUTHORITY_FIELDS =
        ProviderExecutorPrincipalActivationCanonicalResolutionAdmissionContract::REQUIRED_ACTIVATION_AUTHORITY_FIELDS;
    public const array REQUIRED_REPLAY_CONTENTION_ROOT_FIELDS =
        ProviderExecutorPrincipalActivationCanonicalResolutionAdmissionContract::REQUIRED_REPLAY_CONTENTION_ROOT_FIELDS;
    public const array NON_AUTHORITIES = [
        'resolves_live_custody' => false,
        'creates_resolution_admission' => false,
        'creates_activation_winner' => false,
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
