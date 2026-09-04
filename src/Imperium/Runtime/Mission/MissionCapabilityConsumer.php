<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Mission;

/** Consumer-only authority boundary: it deliberately has no issuance operation. */
interface MissionCapabilityConsumer
{
    public function consume(
        MissionCapability $capability,
        string $missionId,
        string $dossierIdentity,
        string $action,
        string $actor,
        string $target,
        int $at,
    ): array;
}

