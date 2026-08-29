<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

interface ProviderCredentialFamilyPolicy
{
    public function supports(string $providerId, string $familyId): bool;

    public function acceptsReferenceDigest(string $credentialReferenceDigest): bool;
}
