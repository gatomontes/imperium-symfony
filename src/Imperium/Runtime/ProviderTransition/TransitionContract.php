<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Bootstrap\CanonicalJson;

/** New executable protocol; historical inert contracts retain their meanings. */
final class TransitionContract
{
    public const string SCHEMA = 'imperium.provider-successor-executable-transition/v1';
    public const string SCOPE = 'DECIDE_EXACT_PROVIDER_BINDING_SUCCESSOR_ATOMIC_LIVE_TRANSITION';
    public const string CONSUMER = 'la-cortine.executable-provider-successor-transition';
    public const array AUTHORITY_FIELDS = ['schema', 'grant', 'root', 'decision', 'consumer', 'authority_single_use'];
    public const array OUTCOMES = ['COMMITTED', 'REFUSED', 'INCOMPLETE', 'UNKNOWN_REPLAY_PROHIBITED'];
    public const array WRITE_SET = ['authority_consumption', 'v3_admission', 'adoption_join',
        'source_binding_transition', 'successor_binding_activation', 'winner_target', 'receipt_target'];
    public const array GRANT_FIELDS = ['schema', 'storage', 'instance', 'principal', 'generation', 'principal_activation',
        'binding', 'binding_digest', 'successor', 'successor_digest', 'successor_creation',
        'assurance', 'execution_boundary', 'operation', 'scope', 'effective_at', 'expires_at'];

    public static function digest(array $record): string
    {
        return hash('sha256', CanonicalJson::encode($record));
    }

    /** The pin is deployment configuration, never an execute-request argument. */
    public static function grant(array $grant, string $pin): void
    {
        self::keys($grant, self::GRANT_FIELDS);
        if (!preg_match('/^[a-f0-9]{64}$/D', $pin) || !hash_equals($pin, self::digest($grant))
            || self::SCHEMA !== $grant['schema'] || self::SCOPE !== $grant['scope']) {
            throw new \RuntimeException('EAT_GRANT_NOT_TRUSTED');
        }
        foreach (['storage', 'instance', 'principal', 'principal_activation', 'binding', 'binding_digest',
            'successor', 'successor_digest', 'successor_creation', 'assurance', 'execution_boundary', 'operation'] as $key) {
            if (!is_string($grant[$key]) || !preg_match('/^[a-f0-9]{64}$/D', $grant[$key])) {
                throw new \RuntimeException('EAT_GRANT_SHAPE_INVALID');
            }
        }
        if (!is_int($grant['generation']) || $grant['generation'] < 1
            || !is_int($grant['effective_at']) || !is_int($grant['expires_at'])
            || $grant['effective_at'] < 0 || $grant['expires_at'] <= $grant['effective_at']) {
            throw new \RuntimeException('EAT_GRANT_TIME_INVALID');
        }
    }

    public static function keys(array $record, array $expected): void
    {
        $actual = array_keys($record);
        sort($actual); sort($expected);
        if ($actual !== $expected) {
            throw new \RuntimeException('EAT_UNEXPECTED_FIELDS');
        }
    }

    /** Excludes successor/authority so competitors for the same operation share a root. */
    public static function root(array $grant): string
    {
        return self::digest(['instance' => $grant['instance'], 'binding' => $grant['binding'],
            'operation' => $grant['operation']]);
    }

    public static function current(array $grant, int $at): void
    {
        if ($at < $grant['effective_at'] || $at >= $grant['expires_at']) {
            throw new \RuntimeException('EAT_AUTHORITY_NOT_CURRENT');
        }
    }
}
