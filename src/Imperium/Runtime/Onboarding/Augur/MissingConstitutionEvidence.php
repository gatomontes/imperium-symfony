<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Augur;
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class MissingConstitutionEvidence implements ConstitutionEvidence
{
    public function verify(array $constitution,array $originals):void
    {
        throw new \RuntimeException('O3_AUTHENTIC_CONSTITUTION_EVIDENCE_MISSING');
    }
}
