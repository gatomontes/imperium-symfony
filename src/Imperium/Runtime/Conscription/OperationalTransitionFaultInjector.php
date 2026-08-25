<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Conscription;

interface OperationalTransitionFaultInjector
{
    public function at(string $checkpoint): void;
}
