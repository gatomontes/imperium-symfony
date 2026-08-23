<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Sortie;

use App\Imperium\Runtime\LaCortine\SortieManifest;

interface SortieToolExecutor
{
    public function supports(string $toolId): bool;

    public function execute(SortieManifest $manifest): SortieToolEvidence;
}
