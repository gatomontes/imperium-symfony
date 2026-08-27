<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Sortie;

interface SortieCognitionProviderInvoker
{
    public function invoke(SortieCognitionAuthority $authority, string $prompt): string;
}
