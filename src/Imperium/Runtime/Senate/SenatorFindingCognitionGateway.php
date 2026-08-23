<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

interface SenatorFindingCognitionGateway
{
    public function find(
        string $jurisdiction,
        array $assignment,
        array $evidence,
    ): array;
}
