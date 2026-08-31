<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final readonly class ProviderBindingActivationReconciliationFixtureInterruptionProofService
{
    public const string CUT_BEFORE_COMMIT = 'BEFORE_IMMUTABLE_COMMIT';
    public const string CUT_AFTER_COMMIT = 'AFTER_IMMUTABLE_COMMIT';

    private ProviderBindingActivationReconciliationFixtureStore $store;

    public function __construct(string $root)
    {
        $this->store = new ProviderBindingActivationReconciliationFixtureStore($root);
    }

    public function putTarget(
        array $target,
        array $principalActivation,
        array $bindingDescriptor,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
        ?string $cut = null,
    ): array {
        $this->before($cut);
        $result = $this->store->putTarget(
            $target,
            $principalActivation,
            $bindingDescriptor,
            $assurance,
            $boundary,
            $at,
        );
        $this->after($cut);

        return $result;
    }

    public function putDecisionInput(
        array $input,
        array $target,
        array $principalActivation,
        array $bindingDescriptor,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
        ?string $cut = null,
    ): array {
        $this->before($cut);
        $result = $this->store->putDecisionInput(
            $input,
            $target,
            $principalActivation,
            $bindingDescriptor,
            $assurance,
            $boundary,
            $at,
        );
        $this->after($cut);

        return $result;
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
        ?string $cut = null,
    ): array {
        $this->before($cut);
        $result = $this->store->putLifecycleSuccessor(
            $successor,
            $input,
            $target,
            $principalActivation,
            $bindingDescriptor,
            $assurance,
            $boundary,
            $at,
        );
        $this->after($cut);

        return $result;
    }

    public function readTarget(string $rootId): array
    {
        return $this->store->readTarget($rootId);
    }

    public function readDecisionInput(string $rootId): array
    {
        return $this->store->readDecisionInput($rootId);
    }

    public function readLifecycleSuccessor(string $rootId): array
    {
        return $this->store->readLifecycleSuccessor($rootId);
    }

    private function before(?string $cut): void
    {
        if (self::CUT_BEFORE_COMMIT === $cut) {
            throw new \RuntimeException('PBR300_INTERRUPTED_BEFORE_IMMUTABLE_COMMIT');
        }
        if (null !== $cut && self::CUT_AFTER_COMMIT !== $cut) {
            throw new \InvalidArgumentException('PBR302_INTERRUPTION_CUT_INVALID');
        }
    }

    private function after(?string $cut): void
    {
        if (self::CUT_AFTER_COMMIT === $cut) {
            throw new \RuntimeException('PBR301_INTERRUPTED_AFTER_IMMUTABLE_COMMIT');
        }
    }
}
