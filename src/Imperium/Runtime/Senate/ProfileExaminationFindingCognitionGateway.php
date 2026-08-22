<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

interface ProfileExaminationFindingCognitionGateway
{
    public function find(string $jurisdiction, array $authority, array $evidence): array;
}
