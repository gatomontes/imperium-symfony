<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Augur;

#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class MissingBaseEvidence implements BaseEvidence
{
    public function verify(array $policy, array $input, array $originals): void
    {
        throw new \RuntimeException('O3_AUTHENTIC_BASE_EVIDENCE_MISSING');
    }
}
