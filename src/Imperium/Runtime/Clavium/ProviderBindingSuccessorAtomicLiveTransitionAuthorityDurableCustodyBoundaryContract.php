<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorAtomicLiveTransitionAuthorityContract;

final class ProviderBindingSuccessorAtomicLiveTransitionAuthorityDurableCustodyBoundaryContract
{
    public const string SCHEMA =
        'imperium.clavium.provider-binding-successor-atomic-live-transition-authority-durable-custody-boundary/v1';
    public const int VERSION = 1;
    public const string STATUS = 'CONTRACT_ONLY_EMPTY';
    public const array REQUIRED_FIELDS = [
        'schema', 'custody_boundary_id', 'instance_id', 'authority_schema',
        'custody_key_kind', 'replay_contention_root', 'authorized_consumer',
        'delivery_schema', 'single_authority', 'authority_present',
        'authority_consumed', 'secret_material_persisted',
        'process_local_identity_persisted', 'continuing_authority', 'status',
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
