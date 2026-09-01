<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class AtomicTransitionEvidenceMutationContract
{
    public const string SCHEMA =
        'imperium.imperator.atomic-transition-evidence-mutation/v1';
    public const string STATUS = 'CONTRACT_ONLY_NOT_APPLIED';
    public const array KINDS = [
        'NONE', 'REMOVE_PATH', 'REPLACE_VALUE', 'REBIND_RECORD',
        'INJECT_SECRET_MARKER',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'mutation_id', 'mutation_kind', 'target_path',
        'replacement_digest', 'expected_validator_error', 'mutation_applied',
        'status', 'sealed', 'record_digest',
    ];

    private function __construct()
    {
    }
}
