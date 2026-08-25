<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Garrison;

interface DeploymentCustodyTransitionFaultInjector
{
    public function after(string $checkpoint): void;
}
