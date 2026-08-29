<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class AtomicProviderExecutionAdmissionContract
{
    public const string SCHEMA = 'imperium.la-cortine.atomic-provider-execution-admission/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'la-cortine.atomic-activation-custody-consumption-transition';
    public const array CONSUMER_POSTURES = [
        'clavium.credential-resolution-boundary',
        'la-cortine.effect-start-journal',
        'la-cortine.execution-reconstructor',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'admission_id', 'instance_id', 'activation', 'custody', 'delivery',
        'execution_claim', 'runtime_principal', 'atomic_consumption', 'provider_request',
        'status', 'admitted_at', 'expires_at', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_ATOMIC_CONSUMPTION_FIELDS = [
        'authoritative_root', 'activation_consumed', 'custody_consumed', 'delivery_consumed',
        'single_transaction', 'committed_pre_resolution', 'committed_pre_io',
    ];
    public const array REQUIRED_PROVIDER_REQUEST_FIELDS = ['operation', 'exact_destination', 'payload_digest', 'request_fingerprint'];
    public const array STATUSES = ['ADMITTED_PRE_RESOLUTION_PRE_IO', 'EXPIRED', 'REVOKED'];
    public const array NON_AUTHORITIES = [
        'issues_activation_authority' => false,
        'activates_provider_binding' => false,
        'issues_credential_capability' => false,
        'reconstructs_credential_capability' => false,
        'changes_provider_request' => false,
        'resolves_credentials' => false,
        'starts_external_io' => false,
        'records_provider_success' => false,
        'authorizes_retry' => false,
        'grants_continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
