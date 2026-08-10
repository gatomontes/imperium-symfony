<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Sortie;

use App\Imperium\Runtime\LaCortine\SortieManifest;

interface SortieCognitionGateway
{
    public function execute(SortieManifest $manifest): SortieCognitionResult;
}
