<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

interface LordSpeakerDispositionCognitionGateway
{
    public function decide(array $authority, array $findingSet): array;
}
