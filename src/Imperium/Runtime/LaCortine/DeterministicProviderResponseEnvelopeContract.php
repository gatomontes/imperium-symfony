<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class DeterministicProviderResponseEnvelopeContract
{
    public const string SCHEMA = 'imperium.la-cortine.deterministic-provider-response-envelope/v1';
    public const string PRODUCER = 'la-cortine.journal-bound-provider-invocation';
    public const array CONSUMERS = [
        'la-cortine.deterministic-raw-provider-result-sealer',
        'la-cortine.deterministic-receipt-reconstructor',
    ];

    public const array REQUIRED_FIELDS = [
        'schema',
        'envelope_id',
        'instance_id',
        'provider_invocation_admission',
        'provider_callback_start',
        'effect_start_journal',
        'execution_claim',
        'source_authorization',
        'request',
        'provider_observation',
        'recovery',
        'produced_by',
        'captured_at',
        'sealed',
        'record_digest',
    ];

    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest'];
    public const array REQUIRED_REQUEST_FIELDS = [
        'operation',
        'destination',
        'payload_digest',
        'provider_idempotency_key',
        'request_fingerprint',
    ];
    public const array REQUIRED_OBSERVATION_FIELDS = [
        'http_status',
        'headers_digest',
        'content_digest',
        'sealed_content_reference',
        'callback_started_at',
        'response_observed_at',
        'received_at',
    ];
    public const array REQUIRED_RECOVERY_FIELDS = [
        'checkpoint',
        'automatic_replay_permitted',
        'provider_reinvoked',
    ];

    private function __construct()
    {
    }
}
