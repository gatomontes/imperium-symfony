<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

interface SubordinatePersonaReviewCognitionGateway
{
    public function review(
        array $candidate,
        array $specification,
        array $case,
    ): array;
}
