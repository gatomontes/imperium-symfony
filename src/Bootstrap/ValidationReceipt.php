<?php

declare(strict_types=1);

namespace App\Bootstrap;

final readonly class ValidationReceipt
{
    public function __construct(
        public string $manifestId,
        public string $charterGeneration,
        public string $artifactSetDigest,
        public string $launcherDigest,
        public string $masterMasonDigest,
        public array $successionCommission,
        public array $manifest,
    ) {
    }
}
