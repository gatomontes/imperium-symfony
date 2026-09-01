<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class ProviderBindingSuccessorAtomicLiveTransitionReadOnlyAggregateReconstructor
{
    public function __construct(
        private readonly ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContractValidator $planValidator,
        private readonly ProviderBindingSuccessorAtomicLiveTransitionDisposableProofClassifier $classifier,
    ) {
    }

    public function reconstruct(array $plan, array $evidence): array
    {
        $this->planValidator->assertPlan($plan);
        $classification = $this->classifier->classify($evidence);
        $directive = $plan['classification_directives'][$classification]
            ?? 'REFUSE_UNKNOWN_CLASSIFICATION';

        return [
            'classification' => $classification,
            'directive' => $directive,
            'replay_contention_root' => $plan['replay_contention_root'],
            'evidence_complete' => 'COMMITTED' === $classification,
            'automatic_repair_performed' => false,
            'state_write_performed' => false,
            'authority_action_performed' => false,
            'provider_effect_started' => false,
            'continuing_authority' => false,
        ];
    }
}
