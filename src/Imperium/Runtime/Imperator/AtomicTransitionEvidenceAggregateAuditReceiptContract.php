<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class AtomicTransitionEvidenceAggregateAuditReceiptContract
{
    public const string SCHEMA = 'imperium.imperator.atomic-transition-evidence-aggregate-audit-receipt/v1';
    public const string STATUS = 'EVIDENCE_BOUND_READ_ONLY_AGGREGATE_AUDIT';
    public const array REQUIRED_FIELDS = [
        'schema', 'receipt_id', 'replay_contention_root',
        'ordered_case_kinds', 'ordered_case_result_references',
        'ordered_result_set_digest', 'capability_manifest_reference',
        'secret_exclusion_proof_reference', 'all_cases_matched', 'read_only',
        'qualification_removed', 'terminal_recomputation_performed',
        'durable_receipt_created', 'continuing_authority', 'status', 'sealed',
        'record_digest',
    ];

    private function __construct()
    {
    }
}
