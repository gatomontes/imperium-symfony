<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Declarative separation of callback execution, reconstruction and recovery. */
final class NativeEffectExecutionRecoverySeparationContract
{
    public const array ACTS = [
        'FIRST_CALLBACK_EXECUTION',
        'READ_ONLY_RECEIPT_RECONSTRUCTION',
        NativeEffectForwardRecoveryClaimContract::ACT,
    ];
    public const array FIRST_CALLBACK_INPUTS = [
        'admission_id', 'continuation_capability', 'payload',
        'idempotency_key', 'current_time', 'provider_double',
    ];
    public const array RECONSTRUCTION_INPUTS = ['receipt_id'];
    public const array FORWARD_RECOVERY_INPUTS = ['forward_recovery_claim_id', 'current_time'];
    public const array RECOVERY_LOCK_ORDER = [
        'admission_continuation_scope',
        'exact_reconciliation_claim_scope',
        'receipt_immutable_store_scope',
    ];
    public const array REQUIRED_INVARIANTS = [
        'custody_before_callback_start' => true,
        'filesystem_lock_across_callback' => false,
        'execute_returns_existing_receipt' => false,
        'execute_binds_preexisting_response' => false,
        'reconstruct_mutates' => false,
        'forward_recovery_accepts_continuation' => false,
        'forward_recovery_accepts_callback' => false,
    ];
}
