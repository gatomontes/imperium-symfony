<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Onboarding\BaseSelection;

use App\Imperium\Runtime\Onboarding\Selection\Shape;

/** Supplied factual observations; source URLs/official labels are NOT authentication. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class Observation
{
    public const AGES = ['catalogue' => 604800000, 'capability' => 604800000, 'tariff' => 86400000, 'access' => 900000];
    public const FIELDS = ['ref', 'source_ref', 'content_ref', 'source_locator', 'category', 'officialness',
        'observed_at', 'effective_from', 'effective_until', 'scope', 'claim_keys', 'disposition', 'reason'];
    public const SCOPE = ['provider', 'model_id', 'model_version', 'configuration_digest', 'profile_ref', 'account_scope', 'data_scope'];

    public static function fromArray(mixed $raw): array
    {
        $v = Shape::object($raw, self::FIELDS); $out = [];
        foreach (['ref', 'source_ref', 'content_ref'] as $key) { $out[$key] = Boundary::ref($v[$key]); }
        foreach (['source_locator', 'reason'] as $key) { $out[$key] = Shape::text($v[$key]); }
        $out['category'] = Boundary::tag($v['category'], array_keys(self::AGES));
        $out['officialness'] = Boundary::tag($v['officialness'], ['OFFICIAL', 'UNVERIFIED']);
        $out['disposition'] = Boundary::tag($v['disposition'], ['PASS', 'FAIL', 'UNKNOWN']);
        foreach (['observed_at', 'effective_from', 'effective_until'] as $key) { $out[$key] = Boundary::nullableInteger($v[$key]); }
        if ($out['effective_from'] !== null && $out['effective_until'] !== null && $out['effective_from'] >= $out['effective_until']) {
            throw new \InvalidArgumentException('BASE_INVALID_EFFECTIVE_INTERVAL');
        }
        $scope = Shape::object($v['scope'], self::SCOPE); $out['scope'] = [];
        foreach (self::SCOPE as $key) {
            $out['scope'][$key] = $key === 'profile_ref' ? Boundary::ref($scope[$key])
                : ($key === 'configuration_digest' ? Shape::digest($scope[$key]) : Shape::text($scope[$key]));
        }
        $out['claim_keys'] = Boundary::texts($v['claim_keys'], true, true);
        return $out;
    }

    public static function sameScope(array $observation, array $binding, array $context): bool
    {
        $s = $observation['scope'];
        return $s['provider'] === $binding['provider'] && $s['model_id'] === $binding['model_id']
            && $s['model_version'] === $binding['model_version'] && $s['configuration_digest'] === $binding['configuration_ref']['digest']
            && $s['profile_ref'] === $context['profile_ref'] && $s['account_scope'] === $context['account_scope']
            && $s['data_scope'] === $context['data_scope'];
    }

    /** Returns the actual supplied finding or an explicit consistency UNKNOWN. */
    public static function finding(array $o, string $claim, string $category, array $binding, array $context): array
    {
        $reason = null; $at = $context['evaluated_at'];
        if (!self::sameScope($o, $binding, $context)) { $reason = 'WRONG_SCOPE'; }
        elseif ($o['category'] !== $category || !in_array($claim, $o['claim_keys'], true)) { $reason = 'WRONG_CLAIM_OR_CATEGORY'; }
        elseif ($o['officialness'] !== 'OFFICIAL') { $reason = 'OFFICIAL_SUPPORT_UNKNOWN'; }
        elseif ($o['observed_at'] === null || $o['effective_from'] === null || $o['effective_until'] === null) { $reason = 'MISSING_TIME_EVIDENCE'; }
        elseif ($o['observed_at'] > $at) { $reason = 'FUTURE_OBSERVATION'; }
        elseif ($at - $o['observed_at'] > self::AGES[$category]) { $reason = 'STALE_OBSERVATION'; }
        elseif ($at < $o['effective_from'] || $at >= $o['effective_until']) { $reason = 'OUTSIDE_EFFECTIVE_INTERVAL'; }
        elseif ($category === 'tariff' && $o['effective_until'] < $context['expires_at']) { $reason = 'TARIFF_EXPIRES_EARLY'; }
        return ['disposition' => $reason === null ? $o['disposition'] : 'UNKNOWN',
            'reason' => $reason ?? $o['reason'], 'ref' => $o['ref']];
    }
}
