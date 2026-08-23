<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

interface ProviderAccessProbe
{
    /**
     * Return classification evidence only. Secret material must never be returned.
     *
     * @return array{status:string,method:string,evidence:array,restrictions:array}
     */
    public function observe(string $provider, string $credentialRef, array $scope): array;
}
