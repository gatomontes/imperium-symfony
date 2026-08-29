<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

final class OneTimeCapabilityDeliveryContract
{
    public const string SCHEMA = 'imperium.clavium.one-time-capability-delivery/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'clavium.atomic-capability-delivery-claim';
    public const array CONSUMER_POSTURES = [
        'clavium.capability-delivery-acknowledgement',
        'la-cortine.atomic-provider-execution-admission',
        'clavium.delivery-reconstructor',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'delivery_id', 'instance_id', 'source_custody', 'source_activation',
        'capability_identity', 'recipient_principal', 'claim', 'delivery', 'consumption',
        'status', 'claimed_at', 'expires_at', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_RECIPIENT_FIELDS = ['runtime_principal_id', 'process_identity_digest', 'execution_id'];
    public const array REQUIRED_CLAIM_FIELDS = ['winner_scope', 'generation', 'single_winner', 'contention_losers_refused'];
    public const array REQUIRED_DELIVERY_FIELDS = ['delivered', 'acknowledged', 'delivered_at', 'acknowledged_at'];
    public const array REQUIRED_CONSUMPTION_FIELDS = ['consumed_pre_io', 'consumed_at', 'external_io_started'];
    public const array STATUSES = ['CLAIMED_UNDELIVERED', 'DELIVERED_UNACKNOWLEDGED', 'DELIVERED_ACKNOWLEDGED', 'CONSUMED_PRE_IO', 'ABANDONED', 'EXPIRED', 'REVOKED'];
    public const array NON_AUTHORITIES = [
        'issues_credential_capability' => false,
        'reconstructs_credential_capability' => false,
        'changes_recipient_after_claim' => false,
        'permits_double_delivery' => false,
        'resolves_credentials' => false,
        'starts_external_io' => false,
        'authorizes_redelivery' => false,
        'authorizes_retry' => false,
    ];

    private function __construct()
    {
    }
}
