<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

interface ProfileExaminationTestimonyCognitionGateway
{
    public function answer(array $question, array $manifestation): array;
}
