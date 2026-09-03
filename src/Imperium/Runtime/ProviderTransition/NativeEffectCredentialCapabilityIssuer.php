<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Issues only a secret-free same-process access token; it never reads a credential. */
final class NativeEffectCredentialCapabilityIssuer
{
    /** @var array<string, NativeEffectCredentialCapability> */
    private array $issued = [];

    public function issue(array $authority, string $processBoundaryId, int $at): NativeEffectCredentialCapability
    {
        if (($authority['schema'] ?? null) !== NativeEffectAuthorityContract::SCHEMA
            || !is_string($authority['authority_id'] ?? null)
            || !is_string($authority['provider']['provider_id'] ?? null)
            || !is_string($authority['credential_scope']['credential_family'] ?? null)
            || !is_string($processBoundaryId) || '' === $processBoundaryId
            || !is_int($authority['expires_at'] ?? null) || $at >= $authority['expires_at']) {
            throw new \RuntimeException('CNE300_CAPABILITY_SCOPE_INVALID');
        }
        $capability = new NativeEffectCredentialCapability(
            'native-effect-capability-'.bin2hex(random_bytes(12)),
            $authority['authority_id'],
            $authority['provider']['provider_id'],
            $authority['credential_scope']['credential_family'],
            $processBoundaryId,
            $authority['expires_at'],
        );
        $this->issued[$capability->capabilityId] = $capability;
        return $capability;
    }

    public function recognizes(NativeEffectCredentialCapability $capability): bool
    {
        return ($this->issued[$capability->capabilityId] ?? null) === $capability;
    }

    public function consume(NativeEffectCredentialCapability $capability): bool
    {
        if (!$this->recognizes($capability)) {
            return false;
        }
        unset($this->issued[$capability->capabilityId]);
        return true;
    }
}
