<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\DeepSeek;

#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class MissingEvidence implements EvidenceVerifier
{
    public function access(array $original, array $grant, int $now): void
    {
        throw new \RuntimeException('O3_ACCOUNT_ZERO_FEE_EVIDENCE_MISSING');
    }
}
