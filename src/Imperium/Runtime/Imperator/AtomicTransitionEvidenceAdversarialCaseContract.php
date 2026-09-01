<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class AtomicTransitionEvidenceAdversarialCaseContract
{
    public const string SCHEMA =
        'imperium.imperator.atomic-transition-evidence-adversarial-case/v1';
    public const string STATUS = 'CONTRACT_ONLY_NOT_EXECUTED';
    public const array KINDS = [
        'INTERRUPTION', 'EXACT_REPLAY', 'CHANGED_EVIDENCE',
        'SAME_ROOT_CONTENTION', 'PARTIAL_WRITE', 'TAMPER',
        'SECRET_EXCLUSION', 'NON_AUTHORITY',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'case_id', 'case_kind', 'replay_contention_root',
        'primary_fixture', 'comparison_fixture', 'mutation', 'expected_result',
        'case_executed', 'finding_derived', 'status', 'sealed', 'record_digest',
    ];

    private function __construct()
    {
    }
}
