<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel;

interface LegateCognitionGateway
{
    public function cognize(array $providerActivation, array $invocationClaim): array;
}
