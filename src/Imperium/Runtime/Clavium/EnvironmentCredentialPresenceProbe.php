<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

final readonly class EnvironmentCredentialPresenceProbe implements ProviderAccessProbe
{
    /** @param array<string,string> $credentialEnvironmentMap */
    public function __construct(private array $credentialEnvironmentMap)
    {
    }

    public function observe(string $provider, string $credentialRef, array $scope): array
    {
        $environmentName = $this->credentialEnvironmentMap[$credentialRef] ?? null;
        if (!is_string($environmentName) || '' === trim($environmentName)) {
            return ['status' => 'ACCESS_UNVERIFIED', 'method' => 'environment-presence',
                'evidence' => ['credential_reference_configured' => false, 'non_empty_secret_present' => false],
                'restrictions' => ['Credential reference has no configured environment mapping.']];
        }
        $secret = $_SERVER[$environmentName] ?? $_ENV[$environmentName] ?? getenv($environmentName);
        $present = is_string($secret) && '' !== $secret;

        return ['status' => $present ? 'ACCESS_AVAILABLE' : 'ACCESS_UNAVAILABLE', 'method' => 'environment-presence',
            'evidence' => ['credential_reference_configured' => true, 'non_empty_secret_present' => $present],
            'restrictions' => $present ? [] : ['Configured environment credential is absent or empty.']];
    }
}
