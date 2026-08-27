<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Guildhall;

interface GuildhallCognitionGateway
{
    public function deliberate(
        string $acceptanceId,
        array $missionPlan,
        array $commissionScope,
        array $occupancy,
        array $completed = [],
        ?callable $progress = null,
        ?callable $checkpoint = null,
    ): array;
}
