<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProviderBindingActivationReconciliationFixtureStore
{
    public const string TARGETS =
        'var/imperium/evidence/provider-binding-activation-state-reconciliation/targets';
    public const string DECISION_INPUTS =
        'var/imperium/evidence/provider-binding-activation-state-reconciliation/decision-inputs';
    public const string LIFECYCLE_SUCCESSORS =
        'var/imperium/evidence/provider-binding-activation-state-reconciliation/lifecycle-successors';

    private ImmutableRecordStore $records;
    private ProviderBindingActivationReconciliationContractValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->records = new ImmutableRecordStore($root, new AtomicTransition($root));
        $this->validator = new ProviderBindingActivationReconciliationContractValidator();
    }

    public function putTarget(
        array $target,
        array $principalActivation,
        array $bindingDescriptor,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
    ): array {
        $this->validator->assertTarget(
            $target,
            $principalActivation,
            $bindingDescriptor,
            $assurance,
            $boundary,
            $at,
        );

        return $this->records->put(self::TARGETS, $target['target_id'], $target);
    }

    public function putDecisionInput(
        array $input,
        array $target,
        array $principalActivation,
        array $bindingDescriptor,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
    ): array {
        $this->validator->assertDecisionInput(
            $input,
            $target,
            $principalActivation,
            $bindingDescriptor,
            $assurance,
            $boundary,
            $at,
        );

        return $this->records->put(self::DECISION_INPUTS, $input['decision_input_id'], $input);
    }

    public function putLifecycleSuccessor(
        array $successor,
        array $input,
        array $target,
        array $principalActivation,
        array $bindingDescriptor,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
    ): array {
        $this->validator->assertSuccessor(
            $successor,
            $input,
            $target,
            $principalActivation,
            $bindingDescriptor,
            $assurance,
            $boundary,
            $at,
        );

        return $this->records->put(
            self::LIFECYCLE_SUCCESSORS,
            $successor['successor_id'],
            $successor,
        );
    }
}
