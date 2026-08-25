<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel;

interface DelegateMissionCognitionGateway
{
    public function invoke(array $claim, array $activation, array $commission): array;
}
