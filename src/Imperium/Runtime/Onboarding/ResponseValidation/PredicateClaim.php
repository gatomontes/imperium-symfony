<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Onboarding\ResponseValidation;

#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class PredicateClaim
{
    private function __construct(public string $disposition, public array $evidenceRefs) {}

    public static function parse(mixed $value, array $frozenEvidence): self
    {
        $v = ResponseShape::object($value, ['disposition', 'evidence_refs']);
        if (!in_array($v['disposition'], ['PASS', 'FAIL', 'UNKNOWN'], true)) { throw new \InvalidArgumentException('INVALID_DISPOSITION'); }
        return new self($v['disposition'], ResponseShape::refs($v['evidence_refs'], $frozenEvidence, $v['disposition'] === 'PASS'));
    }
}
