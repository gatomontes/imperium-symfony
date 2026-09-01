<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class AtomicTransitionIndependentVerificationReportContract
{
    public const string SCHEMA = 'imperium.atomic-transition-independent-verification-report/v1';
    public const array OUTCOMES = ['PASS', 'REFUSED', 'INDETERMINATE', 'NOT_EXECUTED'];
    public const array DOMAINS = [
        'source_and_build', 'receipt_structure', 'origin_and_provenance',
        'trusted_result', 'dependency_graph', 'acceptance_matrix',
        'complete_chain_exclusion', 'non_authority_perimeter',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'report_id', 'verification_id', 'verifier_identity',
        'sanitized_evidence', 'private_receipt_digest', 'domain_outcomes',
        'producer_disposition_imported', 'producer_success_boolean_imported',
        'sanitized', 'receipt_content_retained', 'receipt_locator_retained',
        'private_material_retained', 'provider_binding_status',
        'required_v3_execution_admission', 'unknown_replay_posture', 'read_only',
        'runtime_state_written', 'authority_issued_or_consumed',
        'provider_invoked', 'external_io_started', 'continuing_authority',
        'disposition', 'sealed', 'record_digest',
    ];

    private function __construct()
    {
    }
}
