<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Authority-empty read-only reconstruction and current-Root limitation. */
final class NativeEffectReconciliationHistoricalReconstructionContract
{
    public const string SCHEMA = 'imperium.audit.native-effect-reconciliation-historical-reconstruction/v1';
    public const int VERSION = 1;
    public const array INPUTS = ['receipt_id'];
    public const array JOIN_ORDER = [
        'receipt', 'claim_consumption', 'forward_recovery_claim',
        'authority_consumption', 'reconciliation_authority',
        'reconciliation_issuance_evidence', 'issuance_authority_consumption',
        'issuance_authority', 'issuance_decision', 'native_authority',
        'native_principal', 'source_principal_lifecycle', 'operator_root_act',
    ];
    public const array HISTORY_RULES = [
        'CUR08A_operator_root_revocation_is_current_untimestamped' => 'REFUSED_OPERATOR_ROOT_CURRENTLY_INELIGIBLE',
        'CUR08A_historical_root_reconstruction' => 'EXISTS_FRAGMENTED',
        'CUR08B_native_source_lifecycle_is_timestamped' => 'HISTORICAL_READ_ONLY_RECONSTRUCTION_PERMITTED',
        'CUR08B_requires_current_root_eligibility' => true,
    ];
    public const array REQUIRED_INVARIANTS = [
        'read_only' => true,
        'continuing_authority' => false,
        'repairs_root_history_limitation' => false,
        'creates_decision' => false,
        'creates_authority' => false,
        'delivers_capability' => false,
        'consumes_authority' => false,
        'publishes_evidence' => false,
        'creates_claim' => false,
        'completes_receipt' => false,
        'invokes_provider' => false,
        'resolves_credentials' => false,
    ];

    private function __construct() {}
}
