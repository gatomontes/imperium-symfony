<?php

declare(strict_types=1);

namespace App\Imperium\Runtime;

final readonly class SystemClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
