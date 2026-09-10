<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Onboarding\BaseSelection;

/** Mechanical proposal only. Retained inputs/claims are NOT authenticated or executable. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class BaseProposal
{
    public const ALGORITHM = 'least_cost_initial_augur_fixed_workload_v1';

    public function __construct(public string $status, public BaseInput $input, public array $rows, public ?array $proposedBinding, public array $ties)
    {
        if (!in_array($status, ['PROPOSED_BASE', 'NO_ELIGIBLE_BASE', 'POLICY_TIME_REFUSED', 'ARITHMETIC_REFUSAL'], true)
            || (($status === 'PROPOSED_BASE') !== ($proposedBinding !== null)) || ($status !== 'PROPOSED_BASE' && $ties !== [])) {
            throw new \InvalidArgumentException('BASE_INVALID_RESULT');
        }
    }
}
