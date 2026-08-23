<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Laboratorium;

interface ProfileElaborationCognitionGateway
{
    public function elaborate(array $acceptance, array $authorization): array;
}
