<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Ephemeral same-process access token. It contains no credential reference or secret. */
final readonly class NativeEffectCredentialCapability
{
    public function __construct(
        public string $capabilityId,
        public string $effectAuthorityId,
        public string $providerId,
        public string $credentialFamily,
        public string $processBoundaryId,
        public int $expiresAt,
    ) {}

    public function metadata(): array
    {
        return [
            'capability_id' => $this->capabilityId,
            'effect_authority_id' => $this->effectAuthorityId,
            'provider_id' => $this->providerId,
            'credential_family' => $this->credentialFamily,
            'process_boundary_id' => $this->processBoundaryId,
            'expires_at' => $this->expiresAt,
            'max_uses' => 1,
            'cross_process_transfer_permitted' => false,
            'credential_reference_persisted' => false,
            'credential_secret_persisted' => false,
        ];
    }
}
