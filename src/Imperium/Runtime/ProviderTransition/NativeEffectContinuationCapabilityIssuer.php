<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Process-local custody registry. It has no serialization or reconstruction API. */
final class NativeEffectContinuationCapabilityIssuer
{
    /** @var array<string, NativeEffectContinuationCapability> */
    private array $issued = [];

    /** @var array<string, true> */
    private array $consumed = [];

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
        $capability = new NativeEffectContinuationCapability(
            'native-effect-continuation-'.bin2hex(random_bytes(16)),
            $admission['admission_id'],
            $admission['record_digest'],
            $admission['semantic_effect_tuple_id'],
            $admission['authority_consumption_id'],
            $processBoundaryId,
            $admission['expires_at'],
        );
        $this->issued[$capability->capabilityId] = $capability;
        return $capability;
    }

    public function recognizes(NativeEffectContinuationCapability $capability): bool
    {
        return ($this->issued[$capability->capabilityId] ?? null) === $capability
            && !isset($this->consumed[$capability->capabilityId]);
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
}
