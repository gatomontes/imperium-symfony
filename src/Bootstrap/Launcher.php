<?php

declare(strict_types=1);

namespace App\Bootstrap;

final readonly class Launcher
{
    public function __construct(
        private ManifestValidator $validator,
        private MasterMason $masterMason,
    ) {}

    public function validate(): ValidationReceipt
    {
        return $this->validator->validate();
    }

    public function activate(string $instanceId): array
    {
        throw new \RuntimeException(
            "B242_LEGACY_PRIMORDIAL_BOOTSTRAP_RETIRED: use imperium:activate operator-root v0 activation.",
        );
    }
}
