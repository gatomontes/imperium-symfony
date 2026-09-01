<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ProviderBindingSuccessorAtomicLiveTransitionAuthorityIssuanceBoundaryContract
{
    public const string SCHEMA =
        'imperium.imperator.provider-binding-successor-atomic-live-transition-authority-issuance-boundary/v1';
    public const int VERSION = 1;
    public const string STATUS = 'CONTRACT_ONLY_NOT_ISSUED';
    public const array REQUIRED_FIELDS = [
        'schema', 'issuance_boundary_id', 'instance_id', 'source_decision',
        'source_issuance_target', 'authority_schema',
        'replay_contention_root', 'custody_target', 'delivery_target',
        'authority_single_use', 'authority_exercisable', 'authority_issued',
        'continuing_authority', 'status', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array NON_AUTHORITIES =
        ProviderBindingSuccessorAtomicLiveTransitionAuthorityContract::NON_AUTHORITIES;

    private function __construct()
    {
    }
}
