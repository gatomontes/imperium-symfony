<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class AtomicTransitionEvidenceOriginContract
{
    public const string SCHEMA =
        'imperium.imperator.atomic-transition-evidence-origin/v1';
    public const string STATUS = 'CONTRACT_ONLY_ORIGIN_NOT_ESTABLISHED';
    public const array LIMITATIONS = [
        'DISPOSABLE_INTERNAL_MISSION_ONLY',
        'NO_PROVIDER_OR_EXTERNAL_EFFECT',
        'NO_LIVE_CREDENTIAL_OR_CAPABILITY',
        'ONE_AUTHORITATIVE_FILESYSTEM_ROOT_ONLY',
        'RAW_PRIVATE_EVIDENCE_EXCLUDED',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'evidence_origin_id', 'experiment_id',
        'disposable_mission_id', 'replay_contention_root',
        'disposable_mission_authorization', 'authorized_case_profile',
        'source_repository', 'source_commit', 'source_tree_digest',
        'dirty_tree_refused', 'build_id', 'build_artifact_digest',
        'dependency_lock_digest', 'runtime_version', 'build_command_identity',
        'executor_principal', 'executor_implementation_digest',
        'executor_entry_point', 'execution_environment_class',
        'mission_dossier', 'fixture_set_root', 'recovery_plan',
        'mutation_set_root', 'expected_result_set_root', 'case_set_root',
        'authoritative_evidence_root', 'fixture_custodian', 'origin_producer',
        'issued_at', 'not_before', 'expires_at', 'prior_origin_disposition',
        'limitations', 'sanitized_evidence_package_id',
        'sanitized_evidence_package_digest', 'raw_private_evidence_excluded',
        'single_use', 'authority_empty', 'execution_performed',
        'operational_receipt_created', 'continuing_authority', 'status',
        'sealed', 'record_digest',
    ];

    private function __construct()
    {
    }
}
