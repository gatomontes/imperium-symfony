<?php

declare(strict_types=1);

namespace App\ReproofV2;

/** Data vocabulary only. No authorization, validation, execution or signing. */
final class Contract
{
    public const string PREFIX = 'imperium.atomic-transition-reproof.';
    public const string PROFILE = 'eight-retained-disposable-cases/v2';
    public const array SCHEMAS = [
        'input' => self::PREFIX.'case-input/v2',
        'expected' => self::PREFIX.'expected-result/v2',
        'observed' => self::PREFIX.'observation/v2',
        'matrix' => self::PREFIX.'ordered-matrix/v2',
        'source' => self::PREFIX.'source-manifest/v2',
        'origin' => self::PREFIX.'origin/v2',
        'receipt' => self::PREFIX.'private-receipt/v2',
        'candidate' => self::PREFIX.'sanitized-candidate/v2',
        'report' => self::PREFIX.'independent-report/v2',
        'identity' => self::PREFIX.'public-identity/v2',
        'attestation' => self::PREFIX.'detached-attestation/v2',
    ];
    public const array CASES = [
        'interruption_before_journal', 'interruption_after_journal',
        'interruption_after_winner', 'interruption_after_receipt',
        'exact_replay', 'changed_evidence', 'same_root_contention', 'partial_write',
    ];
    public const array DOMAINS = [
        'source_and_build', 'receipt_structure', 'origin_and_provenance',
        'trusted_result', 'dependency_graph', 'acceptance_matrix',
        'complete_chain_exclusion', 'non_authority_perimeter',
    ];
    public const array FIELDS = [
        'input' => ['schema', 'case_id', 'root', 'cut', 'primary', 'comparison', 'mutation', 'plan', 'auxiliary', 'record_digest'],
        'expected' => ['schema', 'case_id', 'classification', 'directive', 'comparison', 'validator_error', 'findings', 'record_digest'],
        'observed' => ['schema', 'case_id', 'input_digest', 'expected_digest', 'executor_digest', 'classification', 'directive', 'comparison', 'validator_error', 'findings', 'record_digest'],
        'matrix' => ['schema', 'profile', 'cases', 'input_root', 'expected_root', 'observed_root', 'record_digest'],
        'source' => ['schema', 'object_format', 'commit', 'commit_bytes', 'trees', 'files', 'manifest_root', 'record_digest'],
        'origin' => ['schema', 'proof_id', 'source_digest', 'input_root', 'expected_root', 'authorization_digest', 'runtime_version', 'record_digest'],
        'receipt' => ['schema', 'proof_id', 'origin', 'source', 'matrix', 'graph', 'exclusion', 'record_digest'],
        'candidate' => ['schema', 'proof_id', 'source_commit', 'source_manifest_root', 'origin_digest', 'receipt_digest', 'input_root', 'expected_root', 'observed_root', 'retention', 'disposition', 'record_digest'],
        'report' => ['schema', 'proof_id', 'candidate_digest', 'receipt_digest', 'source_commit', 'verifier_root', 'trusted_identity_digest', 'domain_outcomes', 'disposition', 'qualification_removed', 'campaign_closed', 'record_digest'],
        'identity' => ['schema', 'identity_id', 'purpose', 'public_key', 'not_before', 'expires_at', 'verifier_root', 'record_digest'],
        'attestation' => ['schema', 'purpose', 'report_digest', 'identity_digest', 'signature', 'record_digest'],
    ];
    public const string PURPOSE = 'imperium.atomic-transition-reproof.independent-report/v2';
    public const string RETENTION = 'OPERATOR_LOCAL_ONLY_NOT_FOR_UPLOAD_OR_COMMIT';
    public const array PUBLIC_RECORDS = ['candidate', 'report', 'identity', 'attestation'];
    public const array PACKAGE_STATES = ['ABSENT', 'RESERVED', 'RECEIPT_WRITTEN', 'CANDIDATE_WRITTEN', 'FINALIZED'];
    public const array AUTHORITIES = [
        'mission' => false, 'provider' => false, 'external_io' => false,
        'runtime_write' => false, 'signing' => false, 'admission' => false, 'closure' => false,
    ];

    private function __construct() {}
}
