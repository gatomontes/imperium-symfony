<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Onboarding\Selection;

/** Offline pure core; never registered as a Symfony service in this batch. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class AssignmentSelector
{
    public function select(SelectionRule $rule, RoleInput $input): SelectionResult
    {
        // The typed rule is closed to the sole supported algorithm; it grants no authority.
        if ($input->order->kind === 'ranked') {
            $ranked = [];
            foreach ($input->order->tiers as $tier) {
                foreach ($tier['binding_refs'] as $ref) { $ranked[$ref->key()] = true; }
            }
            if (count($ranked) !== count($input->fitting) || array_diff_key($ranked, $input->fitting) !== []) {
                return SelectionResult::refused($input->role, 'INVALID_CAPACITY_ORDER');
            }
        }
        $eligible = array_intersect_key($input->fitting, $input->permitted);
        // Stable evidence projection even if the candidate input order changes.
        uasort($eligible, static fn (Candidate $a, Candidate $b): int => $a->compare($b));
        if ($eligible === []) { return SelectionResult::refused($input->role, 'NO_ELIGIBLE_ASSIGNMENT'); }
        $refs = array_map(static fn (Candidate $c): RecordRef => $c->ref, array_values($eligible));
        if (count($eligible) === 1) { return SelectionResult::selected($input->role, reset($eligible), null, $refs); }
        if ($input->order->kind === 'unknown') { return SelectionResult::refused($input->role, 'CAPACITY_ORDER_UNKNOWN'); }
        $tiers = [];
        foreach ($input->order->tiers as $tier) {
            $members = [];
            foreach ($tier['binding_refs'] as $ref) {
                if (isset($eligible[$ref->key()])) { $members[] = $eligible[$ref->key()]; }
            }
            if ($members !== []) { $tiers[] = $members; }
        }
        $index = intdiv(count($tiers) - 1, 2);
        usort($tiers[$index], static fn (Candidate $a, Candidate $b): int => $a->compare($b));
        return SelectionResult::selected($input->role, $tiers[$index][0], $index, $refs);
    }

    /** Null pairs means independent role permissions; [] permits no whole set.
     * @param null|list<array{courtthane: RecordRef, locksmith: RecordRef}> $permittedPairs
     */
    public function selectSet(SelectionRule $rule, RoleInput $courtthane, RoleInput $locksmith, ?array $permittedPairs): AssignmentSetResult
    {
        if ($courtthane->role !== 'courtyard.courtthane' || $locksmith->role !== 'clavium.locksmith') { throw new \InvalidArgumentException('INVALID_ROLE_ORDER'); }
        $pairs = []; $seen = [];
        if ($permittedPairs !== null) {
            foreach (Shape::list($permittedPairs) as $raw) {
                $pair = Shape::object($raw, ['courtthane', 'locksmith']);
                if (!$pair['courtthane'] instanceof RecordRef || !$pair['locksmith'] instanceof RecordRef) { throw new \InvalidArgumentException('INVALID_PAIR'); }
                $key = json_encode([$pair['courtthane']->key(), $pair['locksmith']->key()], JSON_THROW_ON_ERROR);
                if (isset($seen[$key])) { throw new \InvalidArgumentException('DUPLICATE_PAIR'); }
                $seen[$key] = true; $pairs[] = $pair;
            }
        }
        $c = $this->select($rule, $courtthane); $l = $this->select($rule, $locksmith);
        if ($c->selected === null || $l->selected === null) { return new AssignmentSetResult('ROLE_SELECTION_REFUSED', null, null); }
        if ($permittedPairs !== null) {
            foreach ($pairs as $pair) {
                if ($pair['courtthane']->key() === $c->selected->ref->key() && $pair['locksmith']->key() === $l->selected->ref->key()) {
                    return new AssignmentSetResult('SELECTED', $c, $l);
                }
            }
            return new AssignmentSetResult('SELECTED_ASSIGNMENT_SET_NOT_PERMITTED', null, null);
        }
        return new AssignmentSetResult('SELECTED', $c, $l);
    }
}
