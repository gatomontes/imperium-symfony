<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class DeterministicProviderInvocationAdmissionContract
{
    public const string SCHEMA = 'imperium.la-cortine.deterministic-provider-invocation-admission/v1';

    public const array REQUIRED_FIELDS = [
        'schema',
        'admission_id',
        'instance_id',
        'effect_start_journal',
        'execution_claim',
        'credential_use',
        'provider_request',
        'admitted_at',
        'expires_at',
        'sealed',
        'record_digest',
    ];

    private function __construct()
    {
    }
}
