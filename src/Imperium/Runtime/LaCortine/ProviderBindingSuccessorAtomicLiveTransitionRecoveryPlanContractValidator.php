<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;

final class ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContractValidator
{
    public function assertPlan(array $plan): void
    {
        $digest = $plan['record_digest'] ?? null;
        $plain = $plan;
        unset($plain['record_digest']);

        if (ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContract::REQUIRED_FIELDS
                !== array_keys($plan)
            || ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContract::SCHEMA
                !== ($plan['schema'] ?? null)
            || !$this->identifier($plan['recovery_plan_id'] ?? null)
            || !$this->identifier($plan['instance_id'] ?? null)
            || !$this->identifier($plan['replay_contention_root'] ?? null)
            || ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContract::DIRECTIVES
                !== ($plan['classification_directives'] ?? null)
            || false !== ($plan['automatic_repair_permitted'] ?? null)
            || false !== ($plan['state_write_permitted'] ?? null)
            || false !== ($plan['authority_action_permitted'] ?? null)
            || false !== ($plan['plan_applied'] ?? null)
            || false !== ($plan['continuing_authority'] ?? null)
            || ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContract::STATUS
                !== ($plan['status'] ?? null)
            || true !== ($plan['sealed'] ?? null)
            || !is_string($digest)
            || !preg_match('/^[a-f0-9]{64}$/', $digest)
            || !hash_equals($digest, hash('sha256', CanonicalJson::encode($plain)))) {
            throw new \RuntimeException(
                'PBL940_ATOMIC_TRANSITION_RECOVERY_PLAN_INVALID',
            );
        }
    }

    private function identifier(mixed $value): bool
    {
        return is_string($value)
            && (bool) preg_match('/^[a-z0-9][a-z0-9._:\\/-]{2,220}$/', $value);
    }
}
