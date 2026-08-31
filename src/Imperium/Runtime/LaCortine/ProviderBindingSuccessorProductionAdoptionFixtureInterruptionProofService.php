<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final readonly class ProviderBindingSuccessorProductionAdoptionFixtureInterruptionProofService
{
    public const string CUT_BEFORE_COMMIT = 'BEFORE_IMMUTABLE_COMMIT';
    public const string CUT_AFTER_COMMIT = 'AFTER_IMMUTABLE_COMMIT';

    private ProviderBindingSuccessorProductionAdoptionFixtureStore $store;

    public function __construct(string $root)
    {
        $this->store = new ProviderBindingSuccessorProductionAdoptionFixtureStore($root);
    }

    public function putDecision(
        array $decision,
        array $decisionAuthority,
        array $target,
        array $input,
        array $principal,
        array $binding,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
        ?string $cut = null,
    ): array {
        return $this->execute(
            'putDecision',
            [$decision, $decisionAuthority, $target, $input, $principal, $binding, $assurance, $boundary, $at],
            $cut,
        );
    }

    public function putAuthority(
        array $authority,
        array $decision,
        array $decisionAuthority,
        array $target,
        array $input,
        array $principal,
        array $binding,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
        ?string $cut = null,
    ): array {
        return $this->execute(
            'putAuthority',
            [$authority, $decision, $decisionAuthority, $target, $input, $principal, $binding, $assurance, $boundary, $at],
            $cut,
        );
    }

    public function putAdoptionTarget(
        array $adoption,
        array $successor,
        array $input,
        array $target,
        array $principal,
        array $binding,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
        ?string $cut = null,
    ): array {
        return $this->execute(
            'putAdoptionTarget',
            [$adoption, $successor, $input, $target, $principal, $binding, $assurance, $boundary, $at],
            $cut,
        );
    }

    public function readDecision(string $rootId): array
    {
        return $this->store->readDecision($rootId);
    }

    public function readAuthority(string $rootId): array
    {
        return $this->store->readAuthority($rootId);
    }

    public function readAdoptionTarget(string $rootId): array
    {
        return $this->store->readAdoptionTarget($rootId);
    }

    private function execute(string $method, array $arguments, ?string $cut): array
    {
        if (self::CUT_BEFORE_COMMIT === $cut) {
            throw new \RuntimeException('PBA800_INTERRUPTED_BEFORE_IMMUTABLE_COMMIT');
        }
        if (null !== $cut && self::CUT_AFTER_COMMIT !== $cut) {
            throw new \InvalidArgumentException('PBA802_INTERRUPTION_CUT_INVALID');
        }

        $result = $this->store->{$method}(...$arguments);
        if (self::CUT_AFTER_COMMIT === $cut) {
            throw new \RuntimeException('PBA801_INTERRUPTED_AFTER_IMMUTABLE_COMMIT');
        }

        return $result;
    }
}
