<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class AtomicTransitionEvidenceDerivedCaseResultContract
{
    public const string SCHEMA =
        'imperium.imperator.atomic-transition-evidence-derived-case-result/v1';
    public const string STATUS = 'DERIVED_READ_ONLY_CASE_RESULT';
    public const array REQUIRED_FIELDS = [
        'schema', 'case_reference', 'plan_reference',
        'primary_fixture_reference', 'comparison_fixture_reference',
        'mutation_reference', 'expected_result_reference',
        'replacement_digest_observed', 'observed_classification',
        'observed_directive', 'observed_comparison',
        'observed_validator_error', 'derived_finding_codes',
        'expectation_matched', 'case_executed', 'finding_derived', 'read_only',
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
