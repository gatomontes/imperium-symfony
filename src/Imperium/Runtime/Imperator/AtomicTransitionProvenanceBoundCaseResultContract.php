<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class AtomicTransitionProvenanceBoundCaseResultContract
{
    public const string SCHEMA =
        'imperium.imperator.atomic-transition-provenance-bound-case-result/v1';
    public const string STATUS = 'TRUSTED_CORRIDOR_CASE_RESULT_PRODUCED';
    public const array REQUIRED_FIELDS = [
        'schema', 'result_id', 'execution_provenance_reference',
        'evidence_origin_reference', 'experiment_id', 'disposable_mission_id',
        'replay_contention_root', 'source_commit', 'source_tree_digest',
        'build_id', 'build_artifact_digest', 'dependency_lock_digest',
        'executor_principal', 'executor_implementation_digest',
        'executor_entry_point', 'case_set_root', 'case_reference',
        'plan_reference', 'primary_fixture_reference',
        'comparison_fixture_reference', 'mutation_reference',
        'expected_result_reference', 'derived_result_digest',
        'replacement_digest_observed', 'observed_classification',
        'observed_directive', 'observed_comparison',
        'observed_validator_error', 'derived_finding_codes',
        'expectation_matched', 'case_executed', 'finding_derived',
        'caller_result_accepted', 'read_only', 'journal_persisted',
        'live_lock_acquired', 'state_written_or_repaired',
        'authority_issued_or_consumed', 'execution_admitted',
        'successor_adopted', 'binding_state_changed',
        'durable_winner_or_receipt_created', 'provider_effect_started',
        'continuing_authority', 'status', 'sealed', 'record_digest',
    ];

    private function __construct()
    {
    }
}
