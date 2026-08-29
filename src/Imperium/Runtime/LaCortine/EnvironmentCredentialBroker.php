<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class EnvironmentCredentialBroker implements CredentialBroker
{
    /** @var array<string, CredentialCapability> */
    private array $issued = [];

    /** @var array<string, string> */
    private array $issuedReferences = [];

    /** @var array<string, int> */
    private array $uses = [];

    public function recognizesExactCapability(CredentialCapability $capability): bool
    {
        return ($this->issued[$capability->capabilityId] ?? null) === $capability;
    }

    public function supportsCrossProcessCustody(): bool
    {
        return false;
    }

    public function issue(
        string $credentialRef,
        string $commissionId,
        string $operation,
        \DateTimeImmutable $expiresAt,
        int $maxUses = 1,
    ): CredentialCapability {
        if (!str_starts_with($credentialRef, 'env:')) {
            throw new \InvalidArgumentException('EnvironmentCredentialBroker accepts only env: credential references.');
        }

        $capability = new CredentialCapability(
            'credential-capability.'.bin2hex(random_bytes(12)),
            $credentialRef,
            $commissionId,
            $operation,
            $expiresAt,
            $maxUses,
        );
        $this->issued[$capability->capabilityId] = $capability;
        $this->issuedReferences[$capability->capabilityId] = $credentialRef;

        return $capability;
    }

    public function consume(CredentialCapability $capability, callable $providerOperation): mixed
    {
        if (($this->issued[$capability->capabilityId] ?? null) !== $capability) {
            throw new \RuntimeException('CREDENTIAL_CAPABILITY_UNISSUED: capability was not issued by this broker.');
        }

        $now = new \DateTimeImmutable();
        if ($now >= $capability->expiresAt) {
            throw new \RuntimeException('CREDENTIAL_CAPABILITY_EXPIRED: capability is no longer usable.');
        }

        $used = $this->uses[$capability->capabilityId] ?? 0;
        if ($used >= $capability->maxUses) {
            throw new \RuntimeException('CREDENTIAL_CAPABILITY_CONSUMED: capability use limit has been reached.');
        }

        $credentialRef = $this->issuedReferences[$capability->capabilityId] ?? null;
        if (!is_string($credentialRef)) {
            throw new \RuntimeException('CREDENTIAL_REFERENCE_UNAVAILABLE: issuing broker no longer holds the live reference.');
        }
        $name = substr($credentialRef, 4);
        if ('' === $name) {
            throw new \RuntimeException('CREDENTIAL_REFERENCE_INVALID: environment variable name is missing.');
        }

        $secret = $_SERVER[$name] ?? $_ENV[$name] ?? getenv($name);
        if (!is_string($secret) || '' === $secret) {
            throw new \RuntimeException('CREDENTIAL_UNAVAILABLE: Clavium-referenced environment secret is unavailable.');
        }

        // Consume before provider execution so a failed external attempt cannot replay a one-use capability.
        $this->uses[$capability->capabilityId] = $used + 1;

        return $providerOperation($secret);
    }
}
