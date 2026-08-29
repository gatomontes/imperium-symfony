<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final readonly class CredentialCapability
{
    public string $credentialReferenceDigest;

    public function __construct(
        public string $capabilityId,
        string $credentialRef,
        public string $commissionId,
        public string $operation,
        public \DateTimeImmutable $expiresAt,
        public int $maxUses = 1,
    ) {
        if ('' === trim($capabilityId) || '' === trim($credentialRef) || '' === trim($commissionId) || '' === trim($operation)) {
            throw new \InvalidArgumentException('Credential capabilities require exact opaque identities and scope.');
        }
        if ($maxUses < 1) {
            throw new \InvalidArgumentException('Credential capability maxUses must be positive.');
        }

        $this->credentialReferenceDigest = hash('sha256', $credentialRef);
    }

    /**
     * Intentionally exposes no secret material.
     */
    public function metadata(): array
    {
        return [
            'capability_id' => $this->capabilityId,
            'credential_reference_digest' => $this->credentialReferenceDigest,
            'commission_id' => $this->commissionId,
            'operation' => $this->operation,
            'expires_at' => $this->expiresAt->format(DATE_ATOM),
            'max_uses' => $this->maxUses,
        ];
    }
}
