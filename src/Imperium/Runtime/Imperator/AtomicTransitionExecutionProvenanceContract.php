<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class AtomicTransitionExecutionProvenanceContract
{
    public const string SCHEMA =
        'imperium.imperator.atomic-transition-execution-provenance/v1';
    public const string STATUS = 'CONTRACT_ONLY_EXECUTION_NOT_PERFORMED';
    public const array REQUIRED_FIELDS = [
        'schema', 'execution_provenance_id', 'evidence_origin',
        'experiment_id', 'disposable_mission_id', 'replay_contention_root',
        'source_commit', 'source_tree_digest', 'build_id',
        'build_artifact_digest', 'dependency_lock_digest', 'runtime_version',
        'executor_principal', 'executor_implementation_digest',
        'executor_entry_point', 'execution_environment_class',
        'mission_dossier', 'fixture_set_root', 'recovery_plan',
        'mutation_set_root', 'expected_result_set_root', 'case_set_root',
        'authoritative_evidence_root', 'fixture_custodian', 'origin_producer',
        'authorized_not_before', 'authorized_expires_at', 'limitations',
        'sanitized_evidence_package_id', 'sanitized_evidence_package_digest',
        'trusted_executor_implemented', 'execution_performed',
        'caller_result_accepted', 'result_produced',
        'dependency_graph_derived', 'complete_chain_exclusion_proved',
        'operational_receipt_created', 'authority_empty',
        'continuing_authority', 'status', 'sealed', 'record_digest',
    ];

    private function __construct()
    {
    }
}
