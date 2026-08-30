<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class GovernedProviderExecutionCombinedAdmissionContract
{
    public const string SCHEMA =
        'imperium.la-cortine.governed-provider-execution-admission/v2';
    public const int VERSION = 2;
    public const string PRODUCER_POSTURE =
        'la-cortine.activation-keyed-combined-provider-execution-admission';
    public const array CONSUMER_POSTURES = [
        'clavium.same-process-stationary-credential-resolution-v2',
        'la-cortine.provider-execution-reconstruction-v2',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'admission_id', 'instance_id', 'execution_boundary',
        'executor_principal', 'provider_binding_activation', 'provider_binding',
        'execution_authority', 'request', 'activation_consumption',
        'authority_consumption', 'effect_start', 'status', 'admitted_at',
        'expires_at', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_REQUEST_FIELDS = [
        'request_id', 'commission_id', 'operation', 'exact_destination',
        'payload_digest', 'request_fingerprint',
    ];
    public const array REQUIRED_ACTIVATION_CONSUMPTION_FIELDS = [
        'activation_id', 'activation_digest', 'single_operation', 'consumed',
        'continuing_authority', 'winner_scope', 'revocation_status',
        'revocation_checked_at',
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
    public const string STATUS =
        'COMBINED_ADMITTED_EFFECT_START_PRE_RESOLUTION_PRE_IO';
    public const string CHECKPOINT =
        'ACTIVATION_AND_AUTHORITY_CONSUMED_EFFECT_START_PRE_RESOLUTION_PRE_IO';
    public const string REVOCATION_STATUS =
        'NO_ACTIVATION_REVOCATION_RECORD_AT_ADMISSION';
    public const string WINNER_SCOPE_PREFIX =
        'single-authoritative-root:provider-binding-activation:';
    public const array NON_AUTHORITIES = [
        'issues_activation' => false,
        'issues_execution_authority' => false,
        'activates_principal' => false,
        'activates_provider_binding' => false,
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
