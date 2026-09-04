<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Declarative authority for sealed-response forward completion only. */
final class NativeEffectReconciliationAuthorityContract
{
    public const string SCHEMA = 'imperium.imperator.native-effect-reconciliation-authority/v1';
    public const string HOLDER = 'imperium.la-cortine.canonical-native-effect-forward-recovery/v1';
    public const string ISSUER = 'imperium.imperator.native-effect-reconciliation-authority-issuer/v1';
    public const string ACT = 'FORWARD_COMPLETE_ONLY';
    public const array REQUIRED_FIELDS = [
        'schema', 'authority_id', 'effect_admission', 'callback_start',
        'sealed_response', 'deterministic_receipt_id', 'act', 'holder',
        'issuer', 'effective_at', 'expires_at',
        'provider_invocation_permitted', 'credential_resolution_permitted',
        'callback_reinvocation_permitted', 'automatic_retry_permitted',
        'single_purpose', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_FALSE_FLAGS = [
        'provider_invocation_permitted',
        'credential_resolution_permitted',
        'callback_reinvocation_permitted',
        'automatic_retry_permitted',
    ];
}
