<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Augur;

/** Fixed verifier: authenticate factual content against competent sources, not just its signature or labels. */
interface BaseEvidence
{
    public function verify(array $policy, array $input, array $originals): void;
}
