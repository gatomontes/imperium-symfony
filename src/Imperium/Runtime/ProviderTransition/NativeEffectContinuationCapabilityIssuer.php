<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Process-local custody registry. It has no serialization or reconstruction API. */
final class NativeEffectContinuationCapabilityIssuer
{
    private readonly NativeEffectProcessIncarnation $incarnation;
    private readonly string $issuerBindingId;

    /** @var array<string, NativeEffectContinuationCapability> */
    private array $issued = [];

    /** @var array<string, true> */
    private array $consumed = [];

    public function __construct(?NativeEffectProcessIncarnation $incarnation = null)
    {
        $this->incarnation = $incarnation ?? new NativeEffectProcessIncarnation();
        $this->issuerBindingId = bin2hex(random_bytes(16));
    }

    public function issueForNewWinner(array $admission, string $processBoundaryId): NativeEffectContinuationCapability
    {
        if (NativeEffectAdmissionContract::SCHEMA !== ($admission['schema'] ?? null)
            || !is_string($admission['admission_id'] ?? null)
            || !is_string($admission['record_digest'] ?? null)
            || !is_string($admission['semantic_effect_tuple_id'] ?? null)
            || !is_string($admission['authority_consumption_id'] ?? null)
            || !is_int($admission['expires_at'] ?? null)
            || '' === $processBoundaryId) {
            throw new \RuntimeException('CNE307_CONTINUATION_SCOPE_INVALID');
        }
        $capabilityId = 'native-effect-continuation-'.bin2hex(random_bytes(16));
        $material = $this->bindingMaterial(
            $capabilityId,
            $admission['admission_id'],
            $admission['record_digest'],
            $admission['semantic_effect_tuple_id'],
            $admission['authority_consumption_id'],
        );
        $capability = new NativeEffectContinuationCapability(
            $capabilityId,
            $admission['admission_id'],
            $admission['record_digest'],
            $admission['semantic_effect_tuple_id'],
            $admission['authority_consumption_id'],
            $processBoundaryId,
            $admission['expires_at'],
            $this->incarnation->runtimeProcessId(),
            $this->incarnation->binding($material),
        );
        $this->issued[$capability->capabilityId] = $capability;
        return $capability;
    }

    public function recognizes(NativeEffectContinuationCapability $capability): bool
    {
        return ($this->issued[$capability->capabilityId] ?? null) === $capability
            && !isset($this->consumed[$capability->capabilityId])
            && $capability->runtimeProcessId === $this->currentProcessId()
            && is_string($capability->processIncarnationBinding)
            && $this->incarnation->recognizes(
                $this->bindingMaterial(
                    $capability->capabilityId,
                    $capability->admissionId,
                    $capability->admissionDigest,
                    $capability->semanticEffectTupleId,
                    $capability->authorityConsumptionId,
                ),
                $capability->processIncarnationBinding,
            );
    }

    public function consume(NativeEffectContinuationCapability $capability): bool
    {
        if (!$this->recognizes($capability)) {
            return false;
        }
        unset($this->issued[$capability->capabilityId]);
        $this->consumed[$capability->capabilityId] = true;
        return true;
    }

    public function __serialize(): never
    {
        throw new \LogicException('CNE505_CONTINUATION_ISSUER_SERIALIZATION_PROHIBITED');
    }

    public function __unserialize(array $data): never
    {
        throw new \LogicException('CNE505_CONTINUATION_ISSUER_UNSERIALIZATION_PROHIBITED');
    }

    public function __clone(): void
    {
        throw new \LogicException('CNE505_CONTINUATION_ISSUER_CLONE_PROHIBITED');
    }

    private function bindingMaterial(
        string $capabilityId,
        string $admissionId,
        string $admissionDigest,
        string $tupleId,
        string $authorityConsumptionId,
    ): string {
        return implode("\0", [
            $this->issuerBindingId,
            $capabilityId,
            $admissionId,
            $admissionDigest,
            $tupleId,
            $authorityConsumptionId,
        ]);
    }

    private function currentProcessId(): ?int
    {
        try {
            return $this->incarnation->runtimeProcessId();
        } catch (\RuntimeException) {
            return null;
        }
    }
}
