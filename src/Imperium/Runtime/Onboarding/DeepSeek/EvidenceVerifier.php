<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\DeepSeek;

/** Deployment-selected provenance verifier. Retention/signing alone is not provider evidence. */
interface EvidenceVerifier
{
    public function access(array $original, array $grant, int $now): void;
}
