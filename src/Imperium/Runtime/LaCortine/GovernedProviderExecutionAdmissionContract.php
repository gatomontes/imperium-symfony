<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class GovernedProviderExecutionAdmissionContract
{
    public const string SCHEMA = 'imperium.la-cortine.governed-provider-execution-admission/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'la-cortine.same-root-provider-execution-admission';
    public const array CONSUMER_POSTURES = [
        'clavium.same-process-stationary-credential-resolution',
        'la-cortine.provider-execution-reconstruction',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'admission_id', 'instance_id', 'execution_boundary',
        'executor_principal', 'provider_binding_activation', 'provider_binding',
        'execution_authority', 'request', 'authority_consumption', 'effect_start',
        'status', 'admitted_at', 'expires_at', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_REQUEST_FIELDS = [
        'request_id', 'commission_id', 'operation', 'exact_destination',
        'payload_digest', 'request_fingerprint',
    ];
    public const array REQUIRED_AUTHORITY_CONSUMPTION_FIELDS = [
        'authority_id', 'authority_digest', 'single_use', 'consumed',
        'continuing_authority', 'winner_scope',
    ];
    public const array REQUIRED_EFFECT_START_FIELDS = [
        'checkpoint', 'local_effect_start_committed',
        'credential_resolution_permitted_after_checkpoint',
        'credential_resolved', 'external_io_started',
        'provider_invoked', 'automatic_replay_permitted',
        'exact_admission_continuation_permitted', 'outcome',
    ];
    public const string STATUS = 'ADMITTED_EFFECT_START_PRE_RESOLUTION_PRE_IO';
    public const string CHECKPOINT = 'LOCAL_EFFECT_START_COMMITTED_PRE_RESOLUTION_PRE_IO';
    public const array NON_AUTHORITIES = [
        'issues_execution_authority' => false,
        'issues_credential_capability' => false,
        'reconstructs_credential_capability' => false,
        'resolves_credentials' => false,
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
