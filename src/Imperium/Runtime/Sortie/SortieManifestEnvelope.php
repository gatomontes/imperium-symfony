<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Sortie;

use App\Imperium\Runtime\LaCortine\SortieManifest;

final readonly class SortieManifestEnvelope
{
    public function __construct(
        public SortieManifest $manifest,
        public string $manifestDigest,
    ) {
        if (!preg_match('/^[a-f0-9]{64}$/', $manifestDigest)) {
            throw new \InvalidArgumentException('Sortie manifest envelope requires a lowercase SHA-256 digest.');
        }
    }
}
