<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel\Formation;

/** Integer worst-case ledger; uncertainty never creates available resources. */
final class SessionExposure
{
    public const FIELDS = ['calls', 'input_tokens', 'output_tokens', 'cost_microusd', 'milliseconds'];

    public static function validate(array $limits): void
    {
        if (array_keys($limits) !== self::FIELDS) {
            throw new \RuntimeException('CMF030_LIMITS_INVALID');
        }
        foreach ($limits as $value) {
            // Bound arithmetic before addition/multiplication; never use floats for money.
            if (!is_int($value) || $value < 1 || $value > 1000000000) {
                throw new \RuntimeException('CMF030_LIMITS_INVALID');
            }
        }
    }

    public static function reserve(array &$session, string $attempt, array $maximum, string $fingerprint): array
    {
        self::validate($maximum);
        if (isset($session['attempts'][$attempt])) {
            $prior = $session['attempts'][$attempt];
            if ($prior['fingerprint'] !== $fingerprint || $prior['maximum'] !== $maximum) {
                throw new \RuntimeException('CMF031_ATTEMPT_REPLAY_CONFLICT');
            }
            return $prior;
        }
        $used = array_fill_keys(self::FIELDS, 0);
        foreach ($session['attempts'] ?? [] as $prior) {
            foreach ($used as $field => $value) {
                $used[$field] += ($prior['settled'] ?? $prior['maximum'])[$field];
            }
        }
        foreach ($used as $field => $value) {
            if ($maximum[$field] > $session['per_call'][$field]
                || $maximum[$field] > $session['total'][$field] - $value) {
                throw new \RuntimeException('CMF032_SESSION_EXHAUSTED');
            }
        }
        return $session['attempts'][$attempt] = [
            'fingerprint' => $fingerprint, 'maximum' => $maximum,
            'settled' => null, 'status' => 'RESERVED',
        ];
    }

    public static function settle(array &$attempt, array $usage): void
    {
        if (array_keys($usage) !== self::FIELDS || $usage['calls'] !== 1) {
            throw new \RuntimeException('CMF033_USAGE_UNTRUSTWORTHY');
        }
        foreach ($usage as $field => $value) {
            if (!is_int($value) || $value < 0 || $value > $attempt['maximum'][$field]) {
                throw new \RuntimeException('CMF033_USAGE_UNTRUSTWORTHY');
            }
        }
        if ($attempt['settled'] !== null && $attempt['settled'] !== $usage) {
            throw new \RuntimeException('CMF033_USAGE_UNTRUSTWORTHY');
        }
        $attempt['settled'] = $usage;
    }
}
