<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Exact process-local custody; public metadata is not an authorization token. */
final readonly class NativeEffectReconciliationAuthorityCapability
{
    public function __construct(
        public string $capabilityId,
        public string $authorityId,
        public string $authorityDigest,
        public string $issuanceId,
        public string $issuanceDigest,
        public string $missionId,
        public string $dossierIdentity,
        public int $expiresAt,
        public int $runtimeProcessId,
        public string $processIncarnationBinding,
    ) {}

    public function __serialize(): never
    {
        throw new \LogicException('CNE620_RECONCILIATION_CAPABILITY_SERIALIZATION_PROHIBITED');
    }

    public function __unserialize(array $data): never
    {
        throw new \LogicException('CNE620_RECONCILIATION_CAPABILITY_UNSERIALIZATION_PROHIBITED');
    }

    public function __clone(): void
    {
        throw new \LogicException('CNE620_RECONCILIATION_CAPABILITY_CLONE_PROHIBITED');
    }
}
