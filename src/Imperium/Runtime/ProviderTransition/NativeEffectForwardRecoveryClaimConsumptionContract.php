<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Declarative atomic consumption contract for claim-to-receipt completion. */
final class NativeEffectForwardRecoveryClaimConsumptionContract
{
    public const string SCHEMA = 'imperium.la-cortine.native-effect-forward-recovery-claim-consumption/v1';
    public const string ACT = 'BIND_EXACT_DETERMINISTIC_RECEIPT';
    public const array REQUIRED_FIELDS = [
        'schema', 'consumption_id', 'claim_id', 'receipt_id', 'act',
        'consumed_at', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_INVARIANTS = [
        'claim_consumed_once' => true,
        'exact_claim_receipt_replay_converges' => true,
        'different_receipt_conflicts' => true,
        'receipt_write_uses_same_locked_transition' => true,
        'receipt_replay_is_new_authorization' => false,
    ];
}
