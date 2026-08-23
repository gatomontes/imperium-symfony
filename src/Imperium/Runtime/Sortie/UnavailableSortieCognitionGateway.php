<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Sortie;

use App\Imperium\Runtime\LaCortine\SortieManifest;

final class UnavailableSortieCognitionGateway implements SortieCognitionGateway
{
    public function execute(SortieManifest $manifest): SortieCognitionResult
    {
        throw new \RuntimeException('SORTIE_COGNITION_UNAVAILABLE: no external cognition provider is bound to the sortie runtime.');
    }
}
