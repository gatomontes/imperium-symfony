<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Ephemeral object identity. Its public metadata is not an authentication token. */
final readonly class NativeEffectContinuationCapability
{
    public function __construct(
        public string $capabilityId,
        public string $admissionId,
        public string $admissionDigest,
        public string $semanticEffectTupleId,
        public string $authorityConsumptionId,
        public string $processBoundaryId,
        public int $expiresAt,
        public ?int $runtimeProcessId = null,
        public ?string $processIncarnationBinding = null,
    ) {}

    public function metadata(): array
    {
        return [
            'schema' => NativeEffectContinuationCapabilityContract::SCHEMA,
            'capability_id' => $this->capabilityId,
            'admission_id' => $this->admissionId,
            'admission_digest' => $this->admissionDigest,
            'semantic_effect_tuple_id' => $this->semanticEffectTupleId,
            'authority_consumption_id' => $this->authorityConsumptionId,
            'process_boundary_id' => $this->processBoundaryId,
            'runtime_process_id' => $this->runtimeProcessId,
            'process_incarnation_binding' => $this->processIncarnationBinding,
            'expires_at' => $this->expiresAt,
            'max_uses' => NativeEffectContinuationCapabilityContract::MAX_USES,
            'cross_process_transfer_permitted' => false,
            'durable_persistence_permitted' => false,
            'reconstruction_permitted' => false,
        ];
    }

    public function __serialize(): never
    {
        throw new \LogicException('CNE503_CONTINUATION_SERIALIZATION_PROHIBITED');
    }

    public function __unserialize(array $data): never
    {
        throw new \LogicException('CNE503_CONTINUATION_UNSERIALIZATION_PROHIBITED');
    }

    public function __clone(): void
    {
        throw new \LogicException('CNE503_CONTINUATION_CLONE_PROHIBITED');
    }
}
