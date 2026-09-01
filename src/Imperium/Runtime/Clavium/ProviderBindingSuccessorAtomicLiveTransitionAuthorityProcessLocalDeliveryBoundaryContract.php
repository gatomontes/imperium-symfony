<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorAtomicLiveTransitionAuthorityContract;

final class ProviderBindingSuccessorAtomicLiveTransitionAuthorityProcessLocalDeliveryBoundaryContract
{
    public const string SCHEMA =
        'imperium.clavium.provider-binding-successor-atomic-live-transition-authority-process-local-delivery-boundary/v1';
    public const int VERSION = 1;
    public const string DELIVERY_KIND = 'PROCESS_LOCAL_SINGLE_USE_REFERENCE';
    public const string STATUS = 'CONTRACT_ONLY_NOT_DELIVERED';
    public const array REQUIRED_FIELDS = [
        'schema', 'delivery_boundary_id', 'instance_id', 'authority_schema',
        'custody_source', 'authorized_consumer', 'replay_contention_root',
        'delivery_kind', 'authority_delivered',
        'process_local_identity_materialized', 'secret_material_present',
        'durable_delivery_material_persisted', 'continuing_authority', 'status',
        'sealed', 'record_digest',
    ];
    public const array REQUIRED_CONSUMER_FIELDS = [
        'service', 'transition', 'same_root_lock_required',
    ];
    public const array NON_AUTHORITIES =
        ProviderBindingSuccessorAtomicLiveTransitionAuthorityContract::NON_AUTHORITIES;

    private function __construct()
    {
    }
}
