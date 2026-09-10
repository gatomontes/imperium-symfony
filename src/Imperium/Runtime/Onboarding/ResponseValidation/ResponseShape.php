<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Onboarding\ResponseValidation;

use App\Imperium\Runtime\Onboarding\Selection\{RecordRef, Shape};

/** Structural checks only. References are supplied identities, never admitted records. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class ResponseShape
{
    public static function object(mixed $value, array $keys): array
    {
        if (!$value instanceof JsonObject) { throw new \InvalidArgumentException('INVALID_OBJECT'); }
        $actual = array_keys($value->members); sort($actual); sort($keys);
        if ($actual !== $keys) { throw new \InvalidArgumentException('INVALID_FIELDS'); }
        return $value->members;
    }

    public static function text(mixed $value): string
    {
        $text = Shape::text($value);
        if (preg_match('/\A[\s\p{Z}]*\z/u', $text) === 1) { throw new \InvalidArgumentException('INVALID_TEXT'); }
        return $text;
    }

    public static function texts(mixed $value): array
    {
        return array_map(self::text(...), Shape::list($value));
    }

    public static function ref(mixed $value): RecordRef
    {
        $raw = self::object($value, ['schema', 'id', 'digest']);
        self::text($raw['schema']);
        if (!is_string($raw['id']) || preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._:-]{0,127}\z/', $raw['id']) !== 1) {
            throw new \InvalidArgumentException('INVALID_REFERENCE_ID');
        }
        return RecordRef::decode($raw);
    }

    /** @return list<RecordRef> */
    public static function refs(mixed $value, ?array $allowed = null, bool $nonempty = false): array
    {
        $refs = []; $seen = [];
        foreach (Shape::list($value, $nonempty) as $raw) {
            $ref = self::ref($raw); $key = $ref->key();
            if (isset($seen[$key])) { throw new \InvalidArgumentException('DUPLICATE_REFERENCE'); }
            if ($allowed !== null && !isset($allowed[$key])) { throw new \InvalidArgumentException('REFERENCE_OUTSIDE_CONTEXT'); }
            $seen[$key] = true; $refs[] = $ref;
        }
        return $refs;
    }

    public static function index(array $refs): array
    {
        $index = [];
        foreach ($refs as $ref) { $index[$ref->key()] = $ref; }
        return $index;
    }

    public static function refArray(RecordRef $ref): array
    {
        return ['schema' => $ref->schema, 'id' => $ref->id, 'digest' => $ref->digest];
    }
}
