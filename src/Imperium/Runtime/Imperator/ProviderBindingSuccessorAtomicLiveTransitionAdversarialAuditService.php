<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionReadOnlyAggregateReconstructor;

/** @deprecated Historical caller-boolean audit; permanently disabled. */
final readonly class ProviderBindingSuccessorAtomicLiveTransitionAdversarialAuditService
{
    public const array REQUIRED_PROOFS = [
        'interruption_classifications_proved',
        'exact_replay_convergence_proved',
        'changed_evidence_refusal_proved',
        'same_root_contention_refusal_proved',
        'partial_write_refusal_proved',
        'automatic_repair_refusal_proved',
        'secret_exclusion_proved',
        'non_authority_perimeter_proved',
    ];

    public function __construct(
        private ProviderBindingSuccessorAtomicLiveTransitionReadOnlyAggregateReconstructor $reconstructor,
    ) {
    }

    public function audit(array $plan, array $evidence, array $proofs): array
    {
        throw new \RuntimeException('PBL1015_HISTORICAL_BOOLEAN_AUDIT_DISABLED');
    }
}
