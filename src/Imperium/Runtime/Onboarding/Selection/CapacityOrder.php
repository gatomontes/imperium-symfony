<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Onboarding\Selection;

/** Decodes only the capacity_order field of v2, not the full assessment. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class CapacityOrder
{
    private function __construct(public string $kind, public array $tiers, public ?string $reason, public array $evidenceRefs) {}

    public static function decode(mixed $value): self
    {
        if (!is_array($value)) { throw new \InvalidArgumentException('INVALID_CAPACITY_ORDER'); }
        if (($value['kind'] ?? null) === 'unknown') {
            $v = Shape::object($value, ['kind', 'reason', 'evidence_refs']);
            return new self('unknown', [], Shape::text($v['reason']), Shape::refs($v['evidence_refs']));
        }
        if (($value['kind'] ?? null) !== 'ranked') { throw new \InvalidArgumentException('INVALID_CAPACITY_ORDER'); }
        $v = Shape::object($value, ['kind', 'tiers']); $tiers = []; $seen = [];
        foreach (Shape::list($v['tiers'], true) as $raw) {
            $tier = Shape::object($raw, ['binding_refs', 'rationale', 'evidence_refs']);
            $refs = Shape::refs($tier['binding_refs'], true);
            foreach ($refs as $ref) {
                if (isset($seen[$ref->key()])) { throw new \InvalidArgumentException('INVALID_CAPACITY_ORDER'); }
                $seen[$ref->key()] = true;
            }
            $tiers[] = ['binding_refs' => $refs, 'rationale' => Shape::text($tier['rationale']), 'evidence_refs' => Shape::refs($tier['evidence_refs'], true)];
        }
        return new self('ranked', $tiers, null, []);
    }
}
