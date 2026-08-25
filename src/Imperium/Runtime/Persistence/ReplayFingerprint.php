<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Persistence;

use App\Bootstrap\CanonicalJson;

final class ReplayFingerprint
{
    public static function of(array $authoritativeInputs): string
    {
        return hash('sha256', CanonicalJson::encode($authoritativeInputs));
    }

    public static function requireMatch(?string $recorded, array $authoritativeInputs, string $error): void
    {
        $expected = self::of($authoritativeInputs);
        if (!is_string($recorded) || !hash_equals($recorded, $expected)) {
            throw new \RuntimeException($error);
        }
    }
}
