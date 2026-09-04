<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Declarative claim derived from one consumed canonical authority. */
final class NativeEffectForwardRecoveryClaimV2Contract
{
    public const string SCHEMA = 'imperium.la-cortine.native-effect-forward-recovery-claim/v2';
    public const string ACT = NativeEffectReconciliationAuthorityV2Contract::ACT;
    public const array REQUIRED_FIELDS = [
        'schema', 'claim_id', 'reconciliation_authority', 'authority_issuance',
        'authority_consumption', 'effect_admission', 'callback_start',
        'sealed_response', 'deterministic_receipt_id', 'act',
        'provider_invocation_permitted', 'credential_resolution_permitted',
        'callback_reinvocation_permitted', 'automatic_retry_permitted',
        'continuing_authority', 'admitted_at', 'expires_at',
        'sealed', 'record_digest',
    ];
    public const array REQUIRED_FALSE_FLAGS = [
        'provider_invocation_permitted', 'credential_resolution_permitted',
        'callback_reinvocation_permitted', 'automatic_retry_permitted',
        'continuing_authority',
    ];
}
