<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

interface SubordinatePersonaSpecificationRevisionCognitionGateway
{
    public function revise(array $case, array $priorSpecification, array $clarificationReturn): array;
}
