<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class AtomicTransitionEvidenceFixtureContract
{
    public const string SCHEMA =
        'imperium.imperator.atomic-transition-evidence-fixture/v1';
    public const string STATUS = 'IMMUTABLE_CONTRACT_ONLY_NOT_EXECUTED';
    public const array KINDS = [
        'EMPTY', 'JOURNAL_ONLY', 'JOURNAL_AND_WINNER', 'COMPLETE',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'fixture_id', 'instance_id', 'replay_contention_root',
        'fixture_kind', 'evidence', 'source_contracts', 'immutable', 'status',
        'sealed', 'record_digest',
    ];

    private function __construct()
    {
    }
}
