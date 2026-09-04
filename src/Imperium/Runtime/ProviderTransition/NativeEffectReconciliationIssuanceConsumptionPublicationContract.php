<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Declarative atomic cut; it consumes and publishes nothing itself. */
final class NativeEffectReconciliationIssuanceConsumptionPublicationContract
{
    public const string SCHEMA = 'imperium.imperator.native-effect-reconciliation-issuance-consumption-publication/v1';
    public const int VERSION = 1;
    public const string STATUS = 'CONTRACT_ONLY_NOT_CONSUMED_OR_PUBLISHED';
    public const string LOCK_IDENTITY = 'reconciliation-issuance-root:{sha256(issuance_authority_id)}';
    public const array GLOBAL_LOCK_ORDER = [
        'reconciliation_issuance_root',
        'issuance_authority_consumption',
        'reconciliation_authority_publication',
        'reconciliation_issuance_evidence_publication',
        'reconciliation_authority_claim_use',
        'forward_recovery_claim_consumption',
        'receipt_publication',
    ];
    public const array PUBLICATION_ORDER = [
        'issuance_authority_consumption',
        'reconciliation_authority',
        'reconciliation_issuance_evidence',
    ];
    public const array INTERRUPTION_CUTS = [
        'BEFORE_CONSUMPTION_NO_OUTPUT',
        'AFTER_CONSUMPTION_BEFORE_AUTHORITY_EXACT_RETRY_ONLY',
        'AFTER_AUTHORITY_BEFORE_ISSUANCE_EVIDENCE_EXACT_RETRY_ONLY',
        'AFTER_ISSUANCE_EVIDENCE_RETURN_ESTABLISHED_RESULT',
    ];
    public const array RETRY_RULES = [
        'exact_decision_authority_issuer_target_and_window_converge' => true,
        'consumed_source_may_only_finish_same_publication' => true,
        'changed_decision_conflicts' => true,
        'changed_authority_conflicts' => true,
        'changed_issuer_conflicts' => true,
        'changed_target_conflicts' => true,
        'changed_lineage_conflicts' => true,
        'changed_validity_window_conflicts' => true,
        'retry_grants_new_authority' => false,
    ];
    public const array REQUIRED_INVARIANTS = [
        'one_consumption_winner' => true,
        'consumption_and_publication_share_one_governed_cut' => true,
        'upstream_lock_acquired_before_downstream_claim_lock' => true,
        'reverse_lock_acquisition_prohibited' => true,
        'external_io_inside_lock' => false,
        'continuing_authority' => false,
        'contract_performs_consumption' => false,
        'contract_performs_publication' => false,
    ];

    private function __construct() {}
}
