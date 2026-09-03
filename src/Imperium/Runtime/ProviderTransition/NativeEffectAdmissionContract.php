<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** One future atomic consumption/effect-start winner; no implementation here. */
final class NativeEffectAdmissionContract
{
    public const string SCHEMA = 'imperium.la-cortine.canonical-native-effect-admission/v1';
    public const string CHECKPOINT = 'EFFECT_STARTED_UNKNOWN_REPLAY_PROHIBITED';
    public const array REQUIRED_FIELDS = [
        'schema', 'admission_id', 'effect_replay_identity', 'native_root',
        'native_receipt', 'effect_authority', 'authority_consumption',
        'effect_start', 'provider_request', 'credential_scope', 'admitted_at',
        'expires_at', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_AUTHORITY_CONSUMPTION_FIELDS = [
        'consumed', 'single_use', 'continuing_authority',
    ];
    public const array REQUIRED_EFFECT_START_FIELDS = [
        'checkpoint', 'outcome', 'automatic_replay_permitted',
        'credential_resolved', 'capability_consumed', 'callback_started',
        'external_io_may_have_started', 'provider_invoked',
    ];
}
