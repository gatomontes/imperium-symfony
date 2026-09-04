<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Exact process-local custody for one future authorized reconciliation issuance. */
final readonly class NativeEffectReconciliationIssuanceAuthorityCapability
{
    public function __construct(
        public string $capabilityId,
        public string $issuanceAuthorityId,
        public string $issuanceAuthorityDigest,
        public string $issuanceDecisionId,
        public string $issuanceDecisionDigest,
        public string $targetAuthorityId,
        public string $targetAuthorityDigest,
        public int $expiresAt,
        public int $runtimeProcessId,
        public string $processIncarnationBinding,
    ) {}

    public function __serialize(): never
    {
        throw new \LogicException('CNE641_ISSUANCE_CAPABILITY_SERIALIZATION_PROHIBITED');
    }

    public function __unserialize(array $data): never
    {
        throw new \LogicException('CNE641_ISSUANCE_CAPABILITY_UNSERIALIZATION_PROHIBITED');
    }

    public function __clone(): void
    {
        throw new \LogicException('CNE641_ISSUANCE_CAPABILITY_CLONE_PROHIBITED');
    }
}
