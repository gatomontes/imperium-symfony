<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class AtomicTransitionEvidenceExpectedResultContract
{
    public const string SCHEMA =
        'imperium.imperator.atomic-transition-evidence-expected-result/v1';
    public const string STATUS = 'CONTRACT_ONLY_NOT_DERIVED';
    public const array CLASSIFICATIONS = [
        'ABSENT', 'PREPARED', 'COMMITTING', 'COMMITTED', 'INCOMPLETE',
    ];
    public const array DIRECTIVES = [
        'NO_ACTION', 'REFUSE_AUTOMATIC_REPAIR', 'REFUSE_PARTIAL_STATE',
        'ACCEPT_EXACT_READ_ONLY', 'REFUSE_INCOMPLETE_EVIDENCE',
    ];
    public const array COMPARISONS = [
        'NOT_APPLICABLE', 'EXACT_REPLAY', 'CHANGED_EVIDENCE_REFUSED',
        'SAME_ROOT_CONTENTION_REFUSED', 'DISTINCT_ROOTS',
        'INCOMPLETE_COMPARISON_REFUSED',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'expected_result_id', 'expected_classification',
        'expected_directive', 'expected_comparison',
        'expected_validator_error', 'expected_finding_codes', 'result_derived',
        'status', 'sealed', 'record_digest',
    ];

    private function __construct()
    {
    }
}
