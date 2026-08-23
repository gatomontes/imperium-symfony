<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

interface SeneschalCognitionGateway
{
    public function decide(string $request, array $context): array;

    public function advance(array $proceeding, array $priorTurns, string $imperatorResponse, array $context): array;
}
