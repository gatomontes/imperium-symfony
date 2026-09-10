<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Onboarding\BaseSelection;

use App\Imperium\Runtime\Onboarding\ResponseValidation\{JsonObject, ResponseShape};
use App\Imperium\Runtime\Onboarding\Selection\Shape;

/** Closed internal projections only. No reference here is an admitted record. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class Boundary
{
    public static function integer(mixed $v, bool $positive = false): int
    {
        if (PHP_INT_SIZE !== 8 || !is_int($v) || $v < ($positive ? 1 : 0)) {
            throw new \InvalidArgumentException('BASE_INVALID_INTEGER');
        }
        return $v;
    }

    public static function nullableInteger(mixed $v): ?int { return $v === null ? null : self::integer($v); }

    public static function tag(mixed $v, array $tags): string
    {
        if (!in_array($v, $tags, true)) { throw new \InvalidArgumentException('BASE_INVALID_TAG'); }
        return $v;
    }

    public static function ref(mixed $v): array
    {
        if (!is_array($v)) { throw new \InvalidArgumentException('BASE_INVALID_REFERENCE'); }
        return ResponseShape::refArray(ResponseShape::ref(new JsonObject($v)));
    }

    public static function key(array $ref): string { return json_encode($ref, JSON_THROW_ON_ERROR); }

    public static function refs(mixed $v): array
    {
        $result = [];
        foreach (Shape::list($v) as $raw) {
            $ref = self::ref($raw); $key = self::key($ref);
            if (isset($result[$key])) { throw new \InvalidArgumentException('BASE_DUPLICATE_REFERENCE'); }
            $result[$key] = $ref;
        }
        ksort($result, SORT_STRING);
        return array_values($result);
    }

    public static function texts(mixed $v, bool $unique = false, bool $nonempty = false): array
    {
        $result = array_map(ResponseShape::text(...), Shape::list($v, $nonempty));
        if ($unique && count(array_unique($result, SORT_STRING)) !== count($result)) {
            throw new \InvalidArgumentException('BASE_DUPLICATE_TEXT');
        }
        sort($result, SORT_STRING);
        return $result;
    }

    public static function frozen(array $refs, array $evidence): void
    {
        foreach ($refs as $ref) {
            if (!isset($evidence[self::key($ref)])) { throw new \InvalidArgumentException('BASE_UNFROZEN_REFERENCE'); }
        }
    }
}
