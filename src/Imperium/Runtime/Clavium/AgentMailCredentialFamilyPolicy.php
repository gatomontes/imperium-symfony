<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

use App\Imperium\Runtime\LaCortine\AgentMailProviderProfile;

final readonly class AgentMailCredentialFamilyPolicy implements ProviderCredentialFamilyPolicy
{
    public function supports(string $providerId, string $familyId): bool
    {
        return AgentMailProviderProfile::PROVIDER_ID === $providerId
            && AgentMailProviderProfile::CREDENTIAL_FAMILY_ID === $familyId;
    }

    public function acceptsReference(string $credentialReference): bool
    {
        return AgentMailProviderProfile::CREDENTIAL_REFERENCE_SYNTAX === $credentialReference;
    }
}
