<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\DeepSeek;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules as R;

/** A verifier projection, not an authority certificate. No character estimator/default. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class TokenEvidence
{
    public function __construct(public string $wireDigest, public string $tokenizer,
        public string $framing, public int $inputMaximum, public int $generatedMaximum,
        public int $contextMinimum)
    {
        R::digest($wireDigest); R::text($tokenizer); R::text($framing);
        R::require($inputMaximum >= 0 && $inputMaximum <= 16384 && $generatedMaximum === 4096
            && $contextMinimum >= 32768, 'TOKEN_BOUND_EVIDENCE');
    }
    public function check(string $wire): void
    {
        R::require($this->wireDigest === 'sha256:'.hash('sha256', $wire), 'TOKEN_WIRE_IDENTITY');
    }
}
