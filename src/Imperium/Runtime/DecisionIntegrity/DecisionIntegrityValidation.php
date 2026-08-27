<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\DecisionIntegrity;

use App\Bootstrap\CanonicalJson;

final class DecisionIntegrityValidation
{
    public static function requireFields(array $record, array $fields, string $error): void
    {
        foreach ($fields as $field) {
            if (!array_key_exists($field, $record)) {
                throw new \RuntimeException($error.':'.$field);
            }
        }
    }

    public static function requireText(mixed $value, string $error): string
    {
        if (!is_string($value) || '' === trim($value)) {
            throw new \RuntimeException($error);
        }

        return trim($value);
    }

    public static function requireList(mixed $value, string $error, bool $allowEmpty = false): array
    {
        if (!is_array($value) || (!array_is_list($value)) || (!$allowEmpty && [] === $value)) {
            throw new \RuntimeException($error);
        }

        return $value;
    }

    public static function requireUtcTime(mixed $value, string $error): \DateTimeImmutable
    {
        $text = self::requireText($value, $error);
        try {
            $time = new \DateTimeImmutable($text);
        } catch (\Exception) {
            throw new \RuntimeException($error);
        }
        if ('+00:00' !== $time->format('P')) {
            throw new \RuntimeException($error);
        }

        return $time;
    }

    public static function validateEvidence(array $evidence, \DateTimeImmutable $effectiveAt, array $requiredFields, string $error): void
    {
        self::requireFields($evidence, $requiredFields, $error);
        foreach (['artifact_id', 'provenance', 'version', 'relevance'] as $field) {
            self::requireText($evidence[$field], $error);
        }
        if (!is_string($evidence['record_digest']) || 1 !== preg_match('/^[a-f0-9]{64}$/', $evidence['record_digest']) || true !== $evidence['sealed']) {
            throw new \RuntimeException($error);
        }
        $observedAt = self::requireUtcTime($evidence['observed_at'], $error);
        $expiresAt = self::requireUtcTime($evidence['expires_at'], $error);
        if ($observedAt > $effectiveAt || $expiresAt <= $effectiveAt) {
            throw new \RuntimeException('DI103_STALE_EVIDENCE');
        }
    }

    public static function requireDigestIntegrity(array $record, string $error): void
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);
        if (!is_string($digest)
            || 1 !== preg_match('/^[a-f0-9]{64}$/', $digest)
            || !hash_equals($digest, hash('sha256', CanonicalJson::encode($record)))) {
            throw new \RuntimeException($error);
        }
    }

    private function __construct()
    {
    }
}
