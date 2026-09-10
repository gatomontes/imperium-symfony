<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Onboarding\Selection;

/** Internal, already-validated input projection; not an authority/admission API. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class RoleInput
{
    private function __construct(public string $role, public array $fitting, public array $permitted, public CapacityOrder $order) {}

    public static function fromValidatedInputs(string $role, array $fitting, array $permitted, CapacityOrder $order, array $frozenEvidence): self
    {
        if (!in_array($role, ['courtyard.courtthane', 'clavium.locksmith'], true)) { throw new \InvalidArgumentException('INVALID_ROLE'); }
        $candidates = []; $identities = []; $profile = null;
        foreach (Shape::list($fitting) as $candidate) {
            if (!$candidate instanceof Candidate || isset($candidates[$candidate->ref->key()])) { throw new \InvalidArgumentException('INVALID_CANDIDATES'); }
            $identity = json_encode($candidate->identity, JSON_THROW_ON_ERROR);
            if (isset($identities[$identity])) { throw new \InvalidArgumentException('AMBIGUOUS_BINDING_IDENTITY'); }
            $identities[$identity] = true;
            $profile ??= $candidate->identity['profile_digest'];
            if ($profile !== $candidate->identity['profile_digest']) { throw new \InvalidArgumentException('MIXED_PROFILES'); }
            $candidates[$candidate->ref->key()] = $candidate;
        }
        $allowed = self::indexRefs($permitted); $evidence = self::indexRefs($frozenEvidence);
        $usedEvidence = $order->evidenceRefs;
        foreach ($order->tiers as $tier) { array_push($usedEvidence, ...$tier['evidence_refs']); }
        foreach ($usedEvidence as $ref) {
            if (!isset($evidence[$ref->key()])) { throw new \InvalidArgumentException('UNFROZEN_CAPACITY_EVIDENCE'); }
        }
        return new self($role, $candidates, $allowed, $order);
    }

    private static function indexRefs(array $refs): array
    {
        $result = [];
        foreach (Shape::list($refs) as $ref) {
            if (!$ref instanceof RecordRef || isset($result[$ref->key()])) { throw new \InvalidArgumentException('INVALID_REFERENCES'); }
            $result[$ref->key()] = $ref;
        }
        return $result;
    }
}
