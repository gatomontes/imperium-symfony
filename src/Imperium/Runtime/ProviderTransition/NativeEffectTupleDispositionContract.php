<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Future tuple winner/loser vocabulary. No disposition is published here. */
final class NativeEffectTupleDispositionContract
{
    public const string SCHEMA = 'imperium.la-cortine.canonical-native-effect-tuple-disposition/v1';
    public const string WINNER = 'TUPLE_WINNER_AUTHORITY_CONSUMED';
    public const string LOSER = 'TUPLE_ALREADY_WON_AUTHORITY_UNCONSUMED';
    public const array REQUIRED_FIELDS = [
        'schema', 'disposition_id', 'semantic_effect_tuple_id',
        'candidate_authority', 'winning_authority', 'outcome',
        'candidate_authority_consumed', 'callback_permitted',
        'continuation_capability_minted', 'automatic_retry_permitted',
        'decided_at', 'sealed', 'record_digest',
    ];
    public const array OUTCOMES = [self::WINNER, self::LOSER];
}
