<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

interface CredentialBroker
{
    /**
     * Issue an opaque, bounded capability for infrastructure use.
     *
     * Implementations must never return credential secret material. The
     * credential reference identifies Clavium custody; the returned object
     * only identifies the authority to cause the scoped authenticated action.
     */
    public function issue(
        string $credentialRef,
        string $commissionId,
        string $operation,
        \DateTimeImmutable $expiresAt,
        int $maxUses = 1,
    ): CredentialCapability;

    /**
     * Consume the opaque capability at the infrastructure boundary.
     *
     * The callback represents the provider adapter receiving authenticated
     * infrastructure context from the implementation. Cognitive callers never
     * receive that context or the underlying secret.
     */
    public function consume(CredentialCapability $capability, callable $providerOperation): mixed;
}
