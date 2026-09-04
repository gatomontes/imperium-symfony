<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Derived durable claim. It cannot authorize a provider callback. */
final class NativeEffectForwardRecoveryClaimContract
{
    public const string SCHEMA = 'imperium.la-cortine.native-effect-forward-recovery-claim/v1';
    public const string ACT = NativeEffectReconciliationAuthorityContract::ACT;
    public const array REQUIRED_FIELDS = [
        'schema', 'claim_id', 'reconciliation_authority', 'effect_admission',
        'callback_start', 'sealed_response', 'deterministic_receipt_id',
        'act', 'provider_invocation_permitted',
        'credential_resolution_permitted', 'callback_reinvocation_permitted',
        'automatic_retry_permitted', 'admitted_at', 'expires_at',
        'sealed', 'record_digest',
    ];
    public const array REQUIRED_INVARIANTS = [
        'durable_for_process_loss' => true,
        'exact_admission_response_binding' => true,
        'idempotent_same_receipt_only' => true,
        'accepted_by_first_execution' => false,
        'provider_invocation_permitted' => false,
        'credential_resolution_permitted' => false,
        'callback_reinvocation_permitted' => false,
        'automatic_retry_permitted' => false,
    ];
}
