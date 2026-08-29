<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

final class OpaqueCapabilityCustodyContract
{
    public const string SCHEMA = 'imperium.clavium.opaque-capability-custody/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'clavium.exact-issued-capability-custody-intake';
    public const array CONSUMER_POSTURES = [
        'clavium.one-time-capability-delivery',
        'la-cortine.atomic-provider-execution-admission',
        'clavium.custody-reconstructor',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'custody_id', 'instance_id', 'source_activation', 'capability_identity',
        'issuer_attestation', 'custodian', 'scope', 'generation', 'status', 'accepted_at',
        'expires_at', 'single_delivery', 'secret_material_persisted',
        'credential_reference_persisted', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_CAPABILITY_IDENTITY_FIELDS = [
        'capability_id', 'identity_digest', 'credential_reference_digest', 'issuer_id',
    ];
    public const array REQUIRED_SCOPE_FIELDS = ['execution_id', 'operation', 'max_uses'];
    public const array STATUSES = ['HELD_UNDELIVERED', 'DELIVERY_CLAIMED', 'DELIVERED_ACKNOWLEDGED', 'CONSUMED_PRE_IO', 'ABANDONED', 'EXPIRED', 'REVOKED'];
    public const array SECRET_EXCLUSION = [
        'credential_reference_permitted' => false,
        'credential_secret_permitted' => false,
        'serialized_capability_permitted' => false,
        'provider_authentication_permitted' => false,
    ];
    public const array NON_AUTHORITIES = [
        'issues_credential_capability' => false,
        'reconstructs_credential_capability' => false,
        'reissues_credential_capability' => false,
        'activates_provider_binding' => false,
        'delivers_without_claim' => false,
        'resolves_credentials' => false,
        'starts_external_io' => false,
        'authorizes_retry' => false,
    ];

    private function __construct()
    {
    }
}
