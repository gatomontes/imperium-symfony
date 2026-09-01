<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class AtomicTransitionExecutorDependencyCapabilityGraphContract
{
    public const string SCHEMA =
        'imperium.imperator.atomic-transition-executor-dependency-capability-graph/v1';
    public const string STATUS = 'ACTUAL_EXECUTOR_GRAPH_DERIVED_READ_ONLY';
    public const array CAPABILITIES = [
        'network_io' => false,
        'filesystem_write' => false,
        'process_execution' => false,
        'environment_access' => false,
        'credential_resolution' => false,
        'provider_invocation' => false,
        'runtime_state_mutation' => false,
    ];
    public const array NODE_FIELDS = [
        'class', 'implementation_digest', 'final', 'readonly_or_stateless',
        'dependencies', 'capabilities',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'graph_id', 'execution_provenance_reference',
        'evidence_origin_reference', 'source_commit', 'source_tree_digest',
        'build_id', 'build_artifact_digest', 'dependency_lock_digest',
        'root_executor_class', 'root_implementation_digest', 'node_count',
        'nodes', 'graph_digest', 'complete_recursive_object_traversal',
        'build_bound', 'unknown_dependencies', 'substituted_dependencies',
        'mutable_dependencies', 'effect_capable_dependencies', 'read_only',
        'runtime_state_written', 'authority_issued_or_consumed',
        'execution_admitted', 'provider_effect_started',
        'continuing_authority', 'status', 'sealed', 'record_digest',
    ];

    private function __construct()
    {
    }
}
