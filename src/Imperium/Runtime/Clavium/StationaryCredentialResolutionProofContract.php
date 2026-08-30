<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

final class StationaryCredentialResolutionProofContract
{
    public const string SCHEMA = 'imperium.clavium.stationary-credential-resolution-proof/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'clavium.same-process-stationary-credential-resolution';
    public const array CONSUMER_POSTURES = [
        'la-cortine.provider-execution-assurance',
        'la-cortine.provider-execution-reconstruction',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'proof_id', 'instance_id', 'provider_execution_admission',
        'execution_authority', 'executor_principal', 'provider_binding',
        'credential_scope', 'resolution', 'effect', 'resolved_at', 'expires_at',
        'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_CREDENTIAL_SCOPE_FIELDS = [
        'provider_id', 'credential_family', 'stationary_possession',
        'same_process_resolution',
    ];
    public const array REQUIRED_RESOLUTION_FIELDS = [
        'checkpoint', 'credential_resolved', 'callback_local',
        'secret_exposed_to_caller', 'credential_reference_persisted',
        'credential_secret_persisted', 'credential_capability_issued',
        'credential_capability_reconstructed',
    ];
    public const array REQUIRED_EFFECT_FIELDS = [
        'provider_invoked', 'external_io_started', 'outbound_byte_sent',
        'provider_outcome_claimed',
    ];
    public const string CHECKPOINT = 'STATIONARY_CREDENTIAL_RESOLVED_CALLBACK_LOCAL_NO_IO';
    public const array NON_AUTHORITIES = [
        'issues_execution_authority' => false,
        'creates_execution_admission' => false,
        'issues_credential_capability' => false,
        'reconstructs_credential_capability' => false,
        'returns_credential_secret' => false,
        'invokes_provider' => false,
        'starts_external_io' => false,
        'records_provider_success' => false,
        'authorizes_retry' => false,
        'opens_iron_gate' => false,
        'opens_lazaretto' => false,
        'grants_continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
