<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class AtomicTransitionEvidenceSecretExclusionProofContract
{
    public const string SCHEMA = 'imperium.imperator.atomic-transition-evidence-secret-exclusion-proof/v1';
    public const string STATUS = 'VALUE_AWARE_SECRET_EXCLUSION_PROVED';
    public const array REQUIRED_VECTOR_KINDS = [
        'SENSITIVE_KEY', 'CREDENTIAL_VALUE', 'ENCODED_CREDENTIAL_VALUE',
        'PROCESS_LOCAL_CAPABILITY_VALUE',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'proof_id', 'scanned_record_digests', 'attack_vector_kinds',
        'attack_vector_digests', 'derived_refusal_codes',
        'all_records_clean', 'all_attacks_refused', 'value_aware', 'read_only',
        'status', 'sealed', 'record_digest',
    ];

    private function __construct()
    {
    }
}
