<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class DeterministicEffectStartJournalContract
{
    public const string SCHEMA = 'imperium.la-cortine.deterministic-effect-start-journal/v1';

    public const array REQUIRED_FIELDS = [
        'schema',
        'journal_id',
        'instance_id',
        'execution_claim',
        'source_authorization',
        'credential_use',
        'provider_safety',
        'effect',
        'started_at',
        'expires_at',
        'sealed',
        'record_digest',
    ];

    public const array REQUIRED_CLAIM_FIELDS = [
        'id',
        'digest',
        'replay_fingerprint',
        'execution_id',
    ];

    public const array REQUIRED_CREDENTIAL_USE_FIELDS = [
        'capability_id',
        'credential_reference_digest',
        'consumption_required',
        'consumed_by_journal',
        'credential_resolved',
    ];

    public const array REQUIRED_PROVIDER_SAFETY_FIELDS = [
        'strategy',
        'provider_idempotency_key',
        'request_fingerprint',
        'provider_contract_reference',
        'automatic_replay_permitted',
    ];

    public const array REQUIRED_EFFECT_FIELDS = [
        'checkpoint',
        'external_io_may_have_started',
        'outcome',
        'provider_invoked_by_transition',
        'resolved_at',
    ];

    private function __construct()
    {
    }
}
