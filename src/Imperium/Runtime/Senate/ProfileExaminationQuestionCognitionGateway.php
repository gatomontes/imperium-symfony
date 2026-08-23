<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

interface ProfileExaminationQuestionCognitionGateway
{
    public function authorQuestion(string $jurisdiction, array $commission, array $opening): array;
}
