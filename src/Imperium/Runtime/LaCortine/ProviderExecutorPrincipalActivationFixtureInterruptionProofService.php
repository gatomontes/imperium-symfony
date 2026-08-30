<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final readonly class ProviderExecutorPrincipalActivationFixtureInterruptionProofService
{
    public const string CUT_BEFORE_COMMIT = 'BEFORE_IMMUTABLE_COMMIT';
    public const string CUT_AFTER_COMMIT = 'AFTER_IMMUTABLE_COMMIT';

    private ProviderExecutorPrincipalActivationFixtureStore $store;

    public function __construct(string $root)
    {
        $this->store = new ProviderExecutorPrincipalActivationFixtureStore($root);
    }

    public function putDecision(
        array $decision,
        array $attestation,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
        ?string $cut = null,
    ): array {
        $this->before($cut);
        $result = $this->store->putDecision(
            $decision,
            $attestation,
            $assurance,
            $boundary,
            $at,
        );
        $this->after($cut);

        return $result;
    }

    public function putActivation(
        array $activation,
        array $decision,
        array $attestation,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
        ?string $cut = null,
    ): array {
        $this->before($cut);
        $result = $this->store->putActivation(
            $activation,
            $decision,
            $attestation,
            $assurance,
            $boundary,
            $at,
        );
        $this->after($cut);

        return $result;
    }

    private function before(?string $cut): void
    {
        if (self::CUT_BEFORE_COMMIT === $cut) {
            throw new \RuntimeException('PEA800_INTERRUPTED_BEFORE_IMMUTABLE_COMMIT');
        }
        if (null !== $cut && self::CUT_AFTER_COMMIT !== $cut) {
            throw new \InvalidArgumentException('PEA802_INTERRUPTION_CUT_INVALID');
        }
    }

    private function after(?string $cut): void
    {
        if (self::CUT_AFTER_COMMIT === $cut) {
            throw new \RuntimeException('PEA801_INTERRUPTED_AFTER_IMMUTABLE_COMMIT');
        }
    }
}
