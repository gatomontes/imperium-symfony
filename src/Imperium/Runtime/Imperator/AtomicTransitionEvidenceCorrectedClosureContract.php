<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class AtomicTransitionEvidenceCorrectedClosureContract
{
    public const string SCHEMA = 'imperium.imperator.atomic-transition-evidence-corrected-closure/v1';
    public const string PRIOR_CLOSURE = 'CAMPAIGN_CLOSURE_ACCEPTED_WITH_MATERIAL_EVIDENCE_DEFECT';
    public const string STATUS = 'CAMPAIGN_CLOSURE_ACCEPTED_AFTER_MATERIAL_EVIDENCE_REMEDIATION';
    public const array REQUIRED_FIELDS = [
        'schema', 'closure_id', 'superseded_closure_status',
        'terminal_recomputation_reference', 'aggregate_receipt_reference',
        'terminal_evidence_chain_digest', 'material_evidence_defect_corrected',
        'qualification_removed', 'campaign_closed', 'provider_binding_status',
        'required_v3_execution_admission', 'unknown_replay_posture',
        'read_only', 'runtime_state_written', 'authority_issued_or_consumed',
        'execution_admitted', 'provider_binding_changed',
        'durable_winner_or_runtime_receipt_created', 'provider_effect_started',
        'continuing_authority', 'status', 'sealed', 'record_digest',
    ];

    private function __construct()
    {
    }
}
