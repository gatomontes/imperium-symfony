<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Augur;

/** Fixed deployment verifier of constitutional provenance and the exact resident artifacts. */
interface ConstitutionEvidence
{
    public function verify(array $constitution,array $originals):void;
}
