<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final readonly class ProviderAssuranceEvidenceFixtureInterruptionProofService
{
    public const string CUT_BEFORE_COMMIT = 'BEFORE_IMMUTABLE_COMMIT';
    public const string CUT_AFTER_COMMIT = 'AFTER_IMMUTABLE_COMMIT';

    private ProviderAssuranceEvidenceFixtureStore $store;

    public function __construct(string $root)
    {
        $this->store = new ProviderAssuranceEvidenceFixtureStore($root);
    }

    public function putSource(array $source, ?string $cut = null): array
    {
        $this->before($cut);
        $result = $this->store->putSource($source);
        $this->after($cut);

        return $result;
    }

    public function putProfile(array $profile, array $sources, ?string $cut = null): array
    {
        $this->before($cut);
        $result = $this->store->putProfile($profile, $sources);
        $this->after($cut);

        return $result;
    }

    public function putAdmission(
        array $admission,
        array $profile,
        array $sources,
        ?string $cut = null,
    ): array {
        $this->before($cut);
        $result = $this->store->putAdmission($admission, $profile, $sources);
        $this->after($cut);

        return $result;
    }

    private function before(?string $cut): void
    {
        if (self::CUT_BEFORE_COMMIT === $cut) {
            throw new \RuntimeException('PER300_INTERRUPTED_BEFORE_IMMUTABLE_COMMIT');
        }
        if (null !== $cut && self::CUT_AFTER_COMMIT !== $cut) {
            throw new \InvalidArgumentException('PER302_INTERRUPTION_CUT_INVALID');
        }
    }

    private function after(?string $cut): void
    {
        if (self::CUT_AFTER_COMMIT === $cut) {
            throw new \RuntimeException('PER301_INTERRUPTED_AFTER_IMMUTABLE_COMMIT');
        }
    }
}
