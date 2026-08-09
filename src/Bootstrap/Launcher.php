<?php

declare(strict_types=1);

namespace App\Bootstrap;

final readonly class Launcher
{
    public function __construct(private ManifestValidator $validator, private MasterMason $masterMason)
    {
    }

    public function validate(): ValidationReceipt
    {
        return $this->validator->validate();
    }

    public function activate(string $instanceId): array
    {
        $receipt = $this->validate();
        return $this->masterMason->executeThroughOfficerBinding($receipt, $instanceId);
    }
}
