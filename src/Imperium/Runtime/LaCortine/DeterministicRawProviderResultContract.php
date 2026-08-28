<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class DeterministicRawProviderResultContract
{
    public const string SCHEMA = 'imperium.la-cortine.deterministic-raw-provider-result/v1';

    public const array REQUIRED_FIELDS = [
        'schema',
        'result_id',
        'instance_id',
        'provider_invocation_admission',
        'execution_claim',
        'provider_outcome',
        'raw_receipt',
        'recovery',
        'recorded_at',
        'sealed',
        'record_digest',
    ];

    public const array REQUIRED_OUTCOME_FIELDS = [
        'status',
        'http_status',
        'provider_receipt_identity',
        'effect_started_at',
        'resolved_at',
        'provider_idempotency_key',
    ];

    public const array REQUIRED_RAW_RECEIPT_FIELDS = [
        'id',
        'schema',
        'content_digest',
        'content_base64',
        'content_type',
        'observed_at',
        'received_at',
    ];

    private function __construct()
    {
    }
}
