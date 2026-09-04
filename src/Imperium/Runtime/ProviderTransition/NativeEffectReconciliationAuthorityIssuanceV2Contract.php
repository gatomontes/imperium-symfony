<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Immutable evidence joining decision, consumed issuance authority and issued result. */
final class NativeEffectReconciliationAuthorityIssuanceV2Contract
{
    public const string SCHEMA = 'imperium.imperator.native-effect-reconciliation-authority-issuance/v2';
    public const array REQUIRED_FIELDS = [
        'schema', 'issuance_id', 'issuance_decision', 'issuance_authority',
        'issuance_authority_consumption', 'issued_authority',
        'source_native_authority', 'source_native_principal',
        'source_native_transition', 'effect_admission', 'issuer_service',
        'issued_at', 'authority_issued', 'provider_invocation_performed',
        'credential_resolution_performed', 'callback_invocation_performed',
        'external_io_performed', 'continuing_authority', 'sealed',
        'record_digest',
    ];
    public const array REQUIRED_FALSE_FLAGS = [
        'provider_invocation_performed', 'credential_resolution_performed',
        'callback_invocation_performed', 'external_io_performed',
        'continuing_authority',
    ];

    private function __construct() {}
}
