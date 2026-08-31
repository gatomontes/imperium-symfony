<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorLiveAdoptionAuthorityContract;

final class ProviderBindingSuccessorLiveAdoptionAtomicWinnerBoundaryValidator
{
    public function assert(array $boundary): void
    {
        $digest = $boundary['record_digest'] ?? null;
        $plain = $boundary;
        unset($plain['record_digest']);

        if (ProviderBindingSuccessorLiveAdoptionAtomicWinnerBoundaryContract::REQUIRED_FIELDS
                !== array_keys($boundary)
            || ProviderBindingSuccessorLiveAdoptionAtomicWinnerBoundaryContract::SCHEMA
                !== ($boundary['schema'] ?? null)
            || true !== ($boundary['sealed'] ?? null)
            || !is_string($digest)
            || !preg_match('/^[a-f0-9]{64}$/', $digest)
            || !hash_equals($digest, hash('sha256', CanonicalJson::encode($plain)))
            || !$this->identifier($boundary['winner_boundary_id'] ?? null)
            || !$this->identifier($boundary['instance_id'] ?? null)
            || !$this->reference($boundary['adoption_decision'] ?? null)
            || ProviderBindingSuccessorLiveAdoptionAuthorityContract::SCHEMA
                !== ($boundary['authority_schema'] ?? null)
            || !$this->reference($boundary['authority_source'] ?? null)
            || !$this->reference($boundary['custody_source'] ?? null)
            || !$this->reference($boundary['completed_successor'] ?? null)
            || !$this->reference($boundary['atomic_creation_winner'] ?? null)
            || !$this->reference($boundary['adoption_target'] ?? null)
            || !$this->reference($boundary['v3_admission'] ?? null)
            || !$this->reference($boundary['adoption_join'] ?? null)
            || !$this->reference($boundary['original_binding'] ?? null)
            || !$this->reference($boundary['successor_binding_target'] ?? null)
            || !$this->identifier($boundary['replay_contention_root'] ?? null)
            || ProviderBindingSuccessorLiveAdoptionAtomicWinnerBoundaryContract::LOCK_KIND
                !== ($boundary['lock_kind'] ?? null)
            || !$this->identifier($boundary['consumer_service'] ?? null)
            || ProviderBindingSuccessorLiveAdoptionAuthorityContract::PERMITTED_TRANSITION
                !== ($boundary['permitted_transition'] ?? null)
            || true !== ($boundary['admission_consumption_adoption_and_binding_atomic'] ?? null)
            || false !== ($boundary['authority_consumed'] ?? null)
            || false !== ($boundary['execution_admitted'] ?? null)
            || false !== ($boundary['successor_adopted'] ?? null)
            || false !== ($boundary['binding_transitioned'] ?? null)
            || false !== ($boundary['partial_record_created'] ?? null)
            || false !== ($boundary['effect_started'] ?? null)
            || false !== ($boundary['continuing_authority'] ?? null)
            || ProviderBindingSuccessorLiveAdoptionAtomicWinnerBoundaryContract::STATUS
                !== ($boundary['status'] ?? null)
            || $this->containsSecret($boundary)) {
            throw new \RuntimeException(
                'PBL300_LIVE_ADOPTION_ATOMIC_WINNER_BOUNDARY_INVALID',
            );
        }
    }

    private function reference(mixed $value): bool
    {
        return is_array($value)
            && ['id', 'digest', 'schema'] === array_keys($value)
            && $this->identifier($value['id'] ?? null)
            && is_string($value['digest'] ?? null)
            && (bool) preg_match('/^[a-f0-9]{64}$/', $value['digest'])
            && $this->identifier($value['schema'] ?? null);
    }

    private function identifier(mixed $value): bool
    {
        return is_string($value)
            && (bool) preg_match('/^[a-z0-9][a-z0-9._:\\/-]{2,220}$/', $value);
    }

    private function containsSecret(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }
        foreach ($value as $key => $item) {
            if (is_string($key) && (bool) preg_match(
                '/(?:credential_(?:bytes|reference|secret|token)|capability_(?:identity|bytes|token)|api[_-]?key|access[_-]?token|authentication_material|environment[_-]?variable|callback_identity|object_identity)/i',
                $key,
            )) {
                return true;
            }
            if ($this->containsSecret($item)) {
                return true;
            }
        }

        return false;
    }
}
