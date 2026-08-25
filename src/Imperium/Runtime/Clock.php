<?php

declare(strict_types=1);

namespace App\Imperium\Runtime;

interface Clock
{
    public function now(): \DateTimeImmutable;
}
