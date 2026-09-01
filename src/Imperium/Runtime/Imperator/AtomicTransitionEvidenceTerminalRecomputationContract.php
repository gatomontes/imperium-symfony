<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class AtomicTransitionEvidenceTerminalRecomputationContract
{
    public const string SCHEMA = 'imperium.imperator.atomic-transition-evidence-terminal-recomputation/v1';
    public const string STATUS = 'MATERIAL_EVIDENCE_DEFECT_CORRECTED_PENDING_CLOSURE';
    public const array REQUIRED_FIELDS = [
        'schema', 'recomputation_id', 'aggregate_receipt_reference',
        'ordered_case_chain_digest', 'recomputed_result_set_digest',
        'recomputed_capability_manifest_digest',
        'recomputed_secret_exclusion_proof_digest',
        'recomputed_aggregate_receipt_digest', 'all_record_seals_recomputed',
        'all_references_recomputed', 'ordered_result_set_recomputed',
        'capability_manifest_recomputed', 'secret_exclusion_proof_recomputed',
        'aggregate_receipt_recomputed', 'material_evidence_defect_corrected',
        'qualification_removed', 'closure_replacement_authorized',
        'terminal_recomputation_performed', 'read_only',
        'journal_persisted', 'live_lock_acquired', 'state_written_or_repaired',
        'authority_issued_or_consumed', 'execution_admitted',
        'successor_adopted', 'binding_state_changed',
        'durable_winner_or_receipt_created', 'provider_effect_started',
        'continuing_authority', 'status', 'sealed', 'record_digest',
    ];

    private function __construct()
    {
    }
}
