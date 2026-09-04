<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Exact process-local custody for one reconciliation issuance authority. */
final readonly class NativeEffectReconciliationIssuanceCapability
{
    public function __construct(
        public string $capabilityId,
        public string $issuanceAuthorityId,
        public string $issuanceAuthorityDigest,
        public string $decisionId,
        public string $decisionDigest,
        public string $admissionId,
        public string $authorityId,
        public string $issuerId,
        public int $effectiveAt,
        public int $expiresAt,
        public int $runtimeProcessId,
        public string $processIncarnationBinding,
    ) {}

    public function __serialize(): never
    {
        throw new \LogicException('CNE640_RECONCILIATION_ISSUANCE_CAPABILITY_SERIALIZATION_PROHIBITED');
    }

    public function __unserialize(array $data): never
    {
        throw new \LogicException('CNE640_RECONCILIATION_ISSUANCE_CAPABILITY_UNSERIALIZATION_PROHIBITED');
    }

    public function __clone(): void
    {
        throw new \LogicException('CNE640_RECONCILIATION_ISSUANCE_CAPABILITY_CLONE_PROHIBITED');
    }
}
