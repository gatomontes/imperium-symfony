<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

final class ProviderBoundCredentialEligibilityContract
{
    public const string SCHEMA = 'imperium.clavium.provider-bound-credential-eligibility/v1';
    public const array REQUIRED_FIELDS = [
        'schema', 'eligibility_id', 'instance_id', 'provider_binding', 'authorization_target',
        'credential_capability', 'provider', 'credential_family', 'status', 'assessed_at',
        'expires_at', 'credential_resolved', 'external_io_permitted', 'sealed', 'record_digest',
    ];
    public const array BOUNDARY = [
        'resolves_credentials' => false,
        'invokes_provider' => false,
        'starts_external_io' => false,
        'grants_continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
