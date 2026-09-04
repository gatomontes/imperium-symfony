<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Declarative atomic consumption contract for authority-to-claim derivation. */
final class NativeEffectReconciliationAuthorityConsumptionContract
{
    public const string SCHEMA = 'imperium.imperator.native-effect-reconciliation-authority-consumption/v1';
    public const string ACT = 'DERIVE_EXACT_FORWARD_RECOVERY_CLAIM';
    public const array REQUIRED_FIELDS = [
        'schema', 'consumption_id', 'authority_id', 'claim_id',
        'custody_capability_id', 'act',
        'consumed_at', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_INVARIANTS = [
        'authority_consumed_once' => true,
        'exact_authority_claim_replay_converges' => true,
        'different_claim_conflicts' => true,
        'claim_write_uses_same_locked_transition' => true,
        'consumption_grants_continuing_authority' => false,
    ];
}
