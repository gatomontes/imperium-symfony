<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Onboarding\ResponseValidation;

use App\Imperium\Runtime\Onboarding\Selection\RecordRef;

/** Claimed FIT is structural consistency only, never verified fitness. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class CandidateClaim
{
    public const PREDICATES = ['identity_runtime_mapping', 'evidence_support', 'minimum_capability',
        'exact_role_profile_fit', 'access', 'admissibility_data_constraints', 'complete_tariff',
        'bounds_usage_support', 'contradictions', 'uncertainty'];

    private function __construct(public RecordRef $bindingRef, public array $predicates, public string $fit, public array $evidenceRefs, public array $limitations) {}

    public static function parse(mixed $value, array $frozenEvidence): self
    {
        $v = ResponseShape::object($value, ['binding_ref', 'predicates', 'fit', 'evidence_refs', 'limitations']);
        if (!in_array($v['fit'], ['FIT', 'NOT_FIT', 'UNKNOWN'], true)) { throw new \InvalidArgumentException('INVALID_FIT'); }
        $predicates = [];
        foreach (ResponseShape::object($v['predicates'], self::PREDICATES) as $name => $raw) {
            $claim = PredicateClaim::parse($raw, $frozenEvidence);
            if ($v['fit'] === 'FIT' && $claim->disposition !== 'PASS') { throw new \InvalidArgumentException('INCONSISTENT_FIT'); }
            $predicates[$name] = $claim;
        }
        return new self(ResponseShape::ref($v['binding_ref']), $predicates, $v['fit'],
            ResponseShape::refs($v['evidence_refs'], $frozenEvidence), ResponseShape::texts($v['limitations']));
    }
}
