<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

interface OperationalCognitionInvocationClaimFaultInjector
{
    public function after(string $checkpoint): void;
}
