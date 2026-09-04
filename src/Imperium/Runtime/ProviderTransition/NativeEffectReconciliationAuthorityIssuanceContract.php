<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Declarative immutable evidence for one reconciliation-authority issuance. */
final class NativeEffectReconciliationAuthorityIssuanceContract
{
    public const string SCHEMA = 'imperium.imperator.native-effect-reconciliation-authority-issuance/v1';
    public const array ROOTED_SOURCE_PATH = [
        'effect_admission.native_root',
        'native_transition_commit.authority_id',
        'resolved_native_authority.current_native_principal',
        'verified_operator_root_act',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'issuance_id', 'mission_id', 'mission_dossier_identity',
        'issued_authority', 'source_native_authority',
        'source_native_principal', 'source_native_transition', 'effect_admission',
        'issuer_service', 'issued_at', 'authority_issued',
        'provider_invocation_performed', 'credential_resolution_performed',
        'callback_invocation_performed', 'external_io_performed',
        'continuing_authority', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_TRUE_FLAGS = ['authority_issued'];
    public const array REQUIRED_FALSE_FLAGS = [
        'provider_invocation_performed', 'credential_resolution_performed',
        'callback_invocation_performed', 'external_io_performed',
        'continuing_authority',
    ];
}
