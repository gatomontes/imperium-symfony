<?php

declare(strict_types=1);

namespace App\ReproofV2;

use App\Bootstrap\CanonicalJson;

/** Neutral serialization/hashing only, shared with independent verification. */
final class Records
{
    public static function hash(mixed $value): string
    {
        return hash('sha256', CanonicalJson::encode($value));
    }

    public static function seal(array $record): array
    {
        unset($record['record_digest']);
        $record['record_digest'] = self::hash($record);
        return $record;
    }

    public static function same(mixed $left, mixed $right): bool
    {
        return CanonicalJson::encode($left) === CanonicalJson::encode($right);
    }
}
