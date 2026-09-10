<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Onboarding\Selection;

/** Structural validation only: never authenticates or admits evidence. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class Shape
{
    public static function object(mixed $value, array $keys): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw new \InvalidArgumentException('INVALID_OBJECT');
        }
        $actual = array_keys($value);
        sort($actual); sort($keys);
        if ($actual !== $keys) { throw new \InvalidArgumentException('INVALID_FIELDS'); }
        return $value;
    }

    public static function text(mixed $value): string
    {
        if (!is_string($value) || trim($value) === '' || preg_match('//u', $value) !== 1) {
            throw new \InvalidArgumentException('INVALID_TEXT');
        }
        return $value;
    }

    public static function digest(mixed $value): string
    {
        if (!is_string($value) || preg_match('/\Asha256:[0-9a-f]{64}\z/', $value) !== 1) {
            throw new \InvalidArgumentException('INVALID_DIGEST');
        }
        return $value;
    }

    public static function list(mixed $value, bool $nonempty = false): array
    {
        if (!is_array($value) || !array_is_list($value) || ($nonempty && $value === [])) {
            throw new \InvalidArgumentException('INVALID_LIST');
        }
        return $value;
    }

    /** @return list<RecordRef> */
    public static function refs(mixed $value, bool $nonempty = false): array
    {
        $refs = []; $seen = [];
        foreach (self::list($value, $nonempty) as $item) {
            $ref = RecordRef::decode($item);
            if (isset($seen[$ref->key()])) { throw new \InvalidArgumentException('DUPLICATE_REFERENCE'); }
            $seen[$ref->key()] = true; $refs[] = $ref;
        }
        return $refs;
    }
}
