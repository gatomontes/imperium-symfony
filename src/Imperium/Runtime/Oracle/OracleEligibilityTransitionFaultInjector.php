<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Oracle;

interface OracleEligibilityTransitionFaultInjector
{
    public function at(string $checkpoint): void;
}
