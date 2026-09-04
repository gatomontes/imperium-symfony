<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Declarative shape of a canonically issued reconciliation authority record. */
final class NativeEffectReconciliationAuthorityV2Contract
{
    public const string SCHEMA = 'imperium.imperator.native-effect-reconciliation-authority/v2';
    public const string ACT = 'FORWARD_COMPLETE_ONLY';
    public const string HOLDER = 'imperium.la-cortine.canonical-native-effect-forward-recovery/v2';
    public const string ISSUER_SERVICE = 'imperium.imperator.native-effect-reconciliation-authority-issuer/v2';
    public const array REQUIRED_FIELDS = [
        'schema', 'authority_id', 'issuance_id', 'mission_id',
        'mission_dossier_identity', 'source_native_authority',
        'source_native_principal', 'source_native_transition', 'effect_admission',
        'callback_start', 'sealed_response', 'deterministic_receipt_id',
        'act', 'holder', 'issuer_service', 'effective_at', 'expires_at',
        'revocation_source', 'single_purpose', 'single_use',
        'provider_invocation_permitted', 'credential_resolution_permitted',
        'callback_reinvocation_permitted', 'automatic_retry_permitted',
        'continuing_authority', 'sealed', 'record_digest',
    ];
    public const array NON_AUTHENTICATING_FIELDS = [
        'schema', 'act', 'holder', 'issuer_service', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_FALSE_FLAGS = [
        'provider_invocation_permitted', 'credential_resolution_permitted',
        'callback_reinvocation_permitted', 'automatic_retry_permitted',
        'continuing_authority',
    ];
}
