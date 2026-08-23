<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

interface PersonaWitnessTestimonyCognitionGateway
{
    public function authorQuestion(
        array $assignment,
        array $deposition,
        array $witness,
    ): array;

    public function answer(
        array $question,
        array $deposition,
        array $witness,
    ): array;
}
