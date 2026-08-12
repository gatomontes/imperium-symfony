<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Guildhall;

interface GuildhallCognitionGateway
{
    public function deliberate(array $missionPlan, array $commissionScope, array $occupancy): array;
}
