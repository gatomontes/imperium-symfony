<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class DeterministicProviderInvocationCheckpointContract
{
    public const string CREDENTIAL_ATTEMPT_SCHEMA = 'imperium.la-cortine.deterministic-credential-consumption-attempt/v1';
    public const string CALLBACK_START_SCHEMA = 'imperium.la-cortine.deterministic-provider-callback-start/v1';
    public const array REQUIRED_FIELDS = ['schema', 'checkpoint_id', 'instance_id', 'provider_invocation_admission', 'execution_claim', 'state', 'recorded_at', 'sealed', 'record_digest'];

    private function __construct()
    {
    }
}
