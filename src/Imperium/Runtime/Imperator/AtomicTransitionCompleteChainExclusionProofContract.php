<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class AtomicTransitionCompleteChainExclusionProofContract
{
    public const string SCHEMA =
        'imperium.imperator.atomic-transition-complete-chain-exclusion-proof/v1';
    public const string STATUS =
        'COMPLETE_CHAIN_SECRET_AND_PROCESS_LOCAL_CAPABILITY_EXCLUSION_PROVED';
    public const array SECTIONS = [
        'evidence_origin', 'execution_provenance', 'fixtures',
        'recovery_plans', 'mutations', 'cases', 'expectations', 'results',
        'dependency_graph', 'aggregates', 'exceptions', 'closure_material',
    ];
    public const array NORMALIZATIONS = [
        'RAW', 'BASE64', 'BASE64URL', 'HEX', 'PERCENT', 'JSON_STRING',
        'SPLIT_SIBLING_CONCATENATION',
    ];
    public const array ATTACK_VECTOR_KINDS = [
        'SENSITIVE_KEY', 'CREDENTIAL_VALUE', 'NESTED_BASE64_CREDENTIAL',
        'BASE64URL_CREDENTIAL', 'HEX_CREDENTIAL', 'PERCENT_CREDENTIAL',
        'JSON_STRING_CREDENTIAL', 'SPLIT_CREDENTIAL_VALUE',
        'PROCESS_LOCAL_CAPABILITY_VALUE', 'PROCESS_LOCAL_OBJECT',
        'PROCESS_LOCAL_RESOURCE', 'EXCEPTION_SECRET',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'proof_id', 'evidence_origin_reference',
        'execution_provenance_reference', 'dependency_graph_reference',
        'scanned_sections', 'scanned_artifact_count',
        'scanned_artifact_digests', 'structural_allowlist_digest',
        'normalizations_applied', 'attack_vector_kinds',
        'attack_vector_digests', 'derived_refusal_codes',
        'all_sections_complete', 'all_artifacts_structurally_allowed',
        'all_artifacts_clean', 'all_attacks_refused', 'value_aware',
        'encoding_aware', 'split_value_aware', 'exception_aware',
        'read_only', 'runtime_state_written', 'authority_issued_or_consumed',
        'execution_admitted', 'provider_effect_started',
        'continuing_authority', 'status', 'sealed', 'record_digest',
    ];

    private function __construct()
    {
    }
}
