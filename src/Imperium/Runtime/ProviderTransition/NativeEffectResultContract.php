<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Provider-neutral result/receipt vocabulary; it grants no retry authority. */
final class NativeEffectResultContract
{
    public const string RESPONSE_SCHEMA = 'imperium.la-cortine.canonical-native-effect-response/v1';
    public const string RECEIPT_SCHEMA = 'imperium.la-cortine.canonical-native-effect-receipt/v1';
    public const array OUTCOMES = ['ACCEPTED', 'REJECTED', 'UNKNOWN_REPLAY_PROHIBITED'];
    public const array REQUIRED_RESPONSE_FIELDS = [
        'schema', 'response_id', 'effect_admission', 'callback_start',
        'provider_observation', 'raw_content', 'recovery', 'sealed_at',
        'sealed', 'record_digest',
    ];
    public const array REQUIRED_RECEIPT_FIELDS = [
        'schema', 'receipt_id', 'effect_admission', 'effect_authority',
        'native_receipt', 'provider_outcome', 'raw_response',
        'lazaretto_admission', 'recovery', 'bound_at', 'continuing_authority',
        'sealed', 'record_digest',
    ];
}
