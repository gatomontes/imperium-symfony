<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Authority-empty rules for future process-local issuance custody. */
final class NativeEffectReconciliationIssuanceCapabilityCustodyContract
{
    public const string STATUS = 'CONTRACT_ONLY_NOT_DELIVERED';
    public const array FUTURE_DELIVERY_INPUTS = ['issuance_authority_id', 'current_time'];
    public const array FUTURE_BINDINGS = [
        'capability_id', 'issuance_authority_id', 'issuance_authority_digest',
        'issuance_decision_id', 'issuance_decision_digest', 'issuer', 'holder',
        'target', 'effect_admission', 'callback_start', 'sealed_response',
        'effective_at', 'expires_at', 'runtime_process_id',
        'process_incarnation_binding',
    ];
    public const array REQUIRED_INVARIANTS = [
        'durable_evidence_is_capability' => false,
        'caller_constructed_value_is_capability' => false,
        'typed_capability_is_process_local' => true,
        'typed_capability_is_exact_object' => true,
        'typed_capability_is_non_serializable' => true,
        'typed_capability_is_non_cloneable' => true,
        'copied_fields_transfer_custody' => false,
        'fresh_process_reuses_old_custody' => false,
        'contract_delivers_capability' => false,
        'contract_creates_authority' => false,
    ];

    private function __construct() {}
}
