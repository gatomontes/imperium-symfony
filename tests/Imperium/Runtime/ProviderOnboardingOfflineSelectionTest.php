<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Onboarding\Selection\{AssignmentSelector, Candidate, CapacityOrder, RecordRef, RoleInput, SelectionRule};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProviderOnboardingOfflineSelectionTest extends TestCase
{
    private static function ref(string $id): array
    {
        return ['schema' => 'imperium.test-public-binding/v1', 'id' => $id, 'digest' => 'sha256:'.hash('sha256', $id)];
    }

    private static function candidate(string $id, ?string $model = null): Candidate
    {
        return Candidate::decode(['ref' => self::ref($id), 'identity' => [
            'provider' => 'test-provider', 'model_id' => $model ?? $id, 'model_version' => 'v1',
            'configuration_digest' => 'sha256:'.str_repeat('a', 64),
            'profile_digest' => 'sha256:'.str_repeat('b', 64), 'binding_digest' => self::ref($id)['digest'],
        ]]);
    }

    private static function ruleBody(): array
    {
        return ['algorithm' => 'role_capacity_middle_tier_v1', 'capacity_basis' => 'augur_profile_specific_evidence_assessment',
            'even_rule' => 'lower_middle', 'same_tier_tie_break' => SelectionRule::IDENTITY_FIELDS,
            'unknown_behavior' => 'refuse_when_multiple_eligible_candidates',
            'group_roles' => ['W2' => 'courtyard.courtthane', 'W3' => 'clavium.locksmith']];
    }

    private static function order(?array $tiers): CapacityOrder
    {
        if ($tiers === null) { return CapacityOrder::decode(['kind' => 'unknown', 'reason' => 'No comparative evidence', 'evidence_refs' => []]); }
        return CapacityOrder::decode(['kind' => 'ranked', 'tiers' => array_map(static fn (array $tier): array => [
            'binding_refs' => array_map(self::ref(...), $tier), 'rationale' => 'Synthetic comparative evidence', 'evidence_refs' => [self::ref('evidence')],
        ], $tiers)]);
    }

    private static function input(array $fit, array $allowed, ?array $tiers, string $role = 'courtyard.courtthane'): RoleInput
    {
        return RoleInput::fromValidatedInputs($role, array_map(self::candidate(...), $fit),
            array_map(static fn (string $id): RecordRef => RecordRef::decode(self::ref($id)), $allowed),
            self::order($tiers), [RecordRef::decode(self::ref('evidence'))]);
    }

    public static function selections(): iterable
    {
        yield 'zero fit' => [[], ['a'], null, 'NO_ELIGIBLE_ASSIGNMENT', null, null];
        yield 'one unknown' => [['a'], ['a'], null, 'SELECTED', 'a', null];
        yield 'one after permission filtering' => [['a', 'b'], ['b'], null, 'SELECTED', 'b', null];
        yield 'no permitted fit' => [['a'], [], null, 'NO_ELIGIBLE_ASSIGNMENT', null, null];
        yield 'three tiers' => [['a', 'b', 'c'], ['a', 'b', 'c'], [['a'], ['b'], ['c']], 'SELECTED', 'b', 1];
        yield 'two lower middle' => [['a', 'b'], ['a', 'b'], [['a'], ['b']], 'SELECTED', 'a', 0];
        yield 'four lower middle' => [['a', 'b', 'c', 'd'], ['a', 'b', 'c', 'd'], [['a'], ['b'], ['c'], ['d']], 'SELECTED', 'b', 1];
        yield 'tier multiplicity does not weight median' => [['a', 'b', 'c', 'd', 'e'], ['a', 'b', 'c', 'd', 'e'], [['a', 'b', 'c'], ['d'], ['e']], 'SELECTED', 'd', 1];
        yield 'filter before median' => [['a', 'b', 'c', 'd', 'e'], ['b', 'd', 'e'], [['a'], ['b'], ['c'], ['d'], ['e']], 'SELECTED', 'd', 1];
        yield 'capacity precedes name' => [['a', 'b', 'c'], ['a', 'b', 'c'], [['c'], ['a'], ['b']], 'SELECTED', 'a', 1];
        yield 'same tier tie' => [['c', 'a', 'b'], ['c', 'a', 'b'], [['c', 'b', 'a']], 'SELECTED', 'a', 0];
        yield 'unknown multiple' => [['a', 'b'], ['a', 'b'], null, 'CAPACITY_ORDER_UNKNOWN', null, null];
        yield 'missing ranked binding' => [['a', 'b'], ['a', 'b'], [['a']], 'INVALID_CAPACITY_ORDER', null, null];
        yield 'extra ranked binding' => [['a'], ['a'], [['a'], ['b']], 'INVALID_CAPACITY_ORDER', null, null];
        yield 'coverage before sole permission' => [['a', 'b'], ['a'], [['a']], 'INVALID_CAPACITY_ORDER', null, null];
        yield 'coverage before zero permission' => [['a', 'b'], [], [['a']], 'INVALID_CAPACITY_ORDER', null, null];
    }

    #[DataProvider('selections')]
    public function testExplicitSelectionOutcomes(array $fit, array $allowed, ?array $tiers, string $status, ?string $selected, ?int $index): void
    {
        $result = (new AssignmentSelector())->select(SelectionRule::decode(self::ruleBody()), self::input($fit, $allowed, $tiers));
        self::assertSame($status, $result->status);
        self::assertSame($selected, $result->selected?->ref->id);
        self::assertSame($index, $result->tierIndex);
        if ($status !== 'SELECTED') { self::assertSame([], $result->eligible); }
    }

    public function testPermutationPreservesSelectionAndEvidenceOrder(): void
    {
        $rule = SelectionRule::decode(self::ruleBody()); $selector = new AssignmentSelector();
        $a = $selector->select($rule, self::input(['a', 'b', 'c'], ['a', 'b', 'c'], [['a', 'b', 'c']]));
        $b = $selector->select($rule, self::input(['c', 'b', 'a'], ['b', 'a', 'c'], [['b', 'c', 'a']]));
        self::assertEquals($a, $b);
        self::assertSame(['a', 'b', 'c'], array_map(static fn (RecordRef $r): string => $r->id, $a->eligible));
    }

    public function testUtf8BytesAndExactDigestTieBreak(): void
    {
        $a = self::candidate('a', 'é'); $b = self::candidate('b', 'z');
        self::assertGreaterThan(0, $a->compare($b)); // UTF-8 c3 follows ASCII 7a; no locale collation.
        $x = self::candidate('a', 'same'); $y = self::candidate('b', 'same');
        // SHA256(a) starts ca; SHA256(b) starts 3e. Remaining identity fields are equal.
        self::assertGreaterThan(0, $x->compare($y));
        $input = RoleInput::fromValidatedInputs('courtyard.courtthane', [$a, $b], [$a->ref, $b->ref], self::order([['a', 'b']]), [RecordRef::decode(self::ref('evidence'))]);
        self::assertSame('b', (new AssignmentSelector())->select(SelectionRule::decode(self::ruleBody()), $input)->selected?->ref->id);
    }

    public static function badOrders(): iterable
    {
        yield 'unknown tag' => [['kind' => 'automatic']];
        yield 'unknown key' => [['kind' => 'unknown', 'reason' => 'unknown', 'evidence_refs' => [], 'authority' => true]];
        yield 'empty reason' => [['kind' => 'unknown', 'reason' => ' ', 'evidence_refs' => []]];
        yield 'boolean reason' => [['kind' => 'unknown', 'reason' => true, 'evidence_refs' => []]];
        yield 'empty tiers' => [['kind' => 'ranked', 'tiers' => []]];
        yield 'map instead of list' => [['kind' => 'ranked', 'tiers' => ['x' => []]]];
        $tier = ['binding_refs' => [self::ref('a')], 'rationale' => 'evidence', 'evidence_refs' => [self::ref('evidence')]];
        yield 'empty tier' => [['kind' => 'ranked', 'tiers' => [array_replace($tier, ['binding_refs' => []])]]];
        yield 'duplicated across tiers' => [['kind' => 'ranked', 'tiers' => [$tier, $tier]]];
        yield 'duplicated in tier' => [['kind' => 'ranked', 'tiers' => [array_replace($tier, ['binding_refs' => [self::ref('a'), self::ref('a')]])]]];
        yield 'ranked requires evidence' => [['kind' => 'ranked', 'tiers' => [array_replace($tier, ['evidence_refs' => []])]]];
        yield 'tier extra field' => [['kind' => 'ranked', 'tiers' => [$tier + ['score' => 2]]]];
        yield 'malformed digest' => [['kind' => 'ranked', 'tiers' => [array_replace($tier, ['binding_refs' => [array_replace(self::ref('a'), ['digest' => 'sha256:ABC'])]])]]];
        yield 'scalar refs' => [['kind' => 'unknown', 'reason' => 'x', 'evidence_refs' => 'anything']];
        yield 'invalid utf8' => [['kind' => 'unknown', 'reason' => "\xff", 'evidence_refs' => []]];
    }

    #[DataProvider('badOrders')]
    public function testMalformedCapacityOrderRefuses(mixed $value): void
    {
        $this->expectException(\InvalidArgumentException::class); CapacityOrder::decode($value);
    }

    public static function badRules(): iterable
    {
        foreach (['algorithm', 'capacity_basis', 'even_rule', 'unknown_behavior'] as $key) {
            yield $key => [array_replace(self::ruleBody(), [$key => 'unsupported'])];
        }
        yield 'extra' => [self::ruleBody() + ['authority' => true]];
        yield 'wrong scalar type' => [array_replace(self::ruleBody(), ['algorithm' => true])];
        yield 'reordered tie break' => [array_replace(self::ruleBody(), ['same_tier_tie_break' => array_reverse(SelectionRule::IDENTITY_FIELDS)])];
        yield 'wrong role' => [array_replace(self::ruleBody(), ['group_roles' => ['W2' => 'oracle.augur', 'W3' => 'clavium.locksmith']])];
        yield 'public wrapper' => [['runtime_body' => self::ruleBody(), 'runnable' => false]];
    }

    #[DataProvider('badRules')]
    public function testRuleIsClosed(mixed $value): void
    {
        $this->expectException(\InvalidArgumentException::class); SelectionRule::decode($value);
    }

    public function testRuleObjectKeyOrderIsIrrelevant(): void
    {
        $body = array_reverse(self::ruleBody(), true); $body['group_roles'] = array_reverse($body['group_roles'], true);
        self::assertEquals(SelectionRule::decode(self::ruleBody()), SelectionRule::decode($body));
    }

    public function testUnfrozenEvidenceCannotSupportSelection(): void
    {
        $this->expectException(\InvalidArgumentException::class); $this->expectExceptionMessage('UNFROZEN_CAPACITY_EVIDENCE');
        RoleInput::fromValidatedInputs('courtyard.courtthane', [self::candidate('a')], [RecordRef::decode(self::ref('a'))], self::order([['a']]), []);
    }

    public function testDuplicateCandidateRefuses(): void
    {
        $this->expectException(\InvalidArgumentException::class); self::input(['a', 'a'], ['a'], null);
    }

    public function testRoleSubstitutionRefuses(): void
    {
        $this->expectException(\InvalidArgumentException::class); self::input(['a'], ['a'], null, 'oracle.augur');
    }

    public function testCoupledPairRefusesWithoutAlternateOrPartialOutput(): void
    {
        $s = new AssignmentSelector(); $rule = SelectionRule::decode(self::ruleBody());
        $c = self::input(['a', 'b'], ['a', 'b'], [['a'], ['b']]);
        $l = self::input(['a', 'b'], ['a', 'b'], [['b'], ['a']], 'clavium.locksmith');
        $result = $s->selectSet($rule, $c, $l, [['courtthane' => RecordRef::decode(self::ref('b')), 'locksmith' => RecordRef::decode(self::ref('a'))]]);
        self::assertSame('SELECTED_ASSIGNMENT_SET_NOT_PERMITTED', $result->status);
        self::assertNull($result->courtthane); self::assertNull($result->locksmith);
        $ok = $s->selectSet($rule, $c, $l, [['courtthane' => RecordRef::decode(self::ref('a')), 'locksmith' => RecordRef::decode(self::ref('b'))]]);
        self::assertSame('a', $ok->courtthane?->selected?->ref->id); self::assertSame('b', $ok->locksmith?->selected?->ref->id);
        self::assertSame('SELECTED', $s->selectSet($rule, $c, $l, null)->status);
        self::assertSame('SELECTED_ASSIGNMENT_SET_NOT_PERMITTED', $s->selectSet($rule, $c, $l, [])->status);
    }

    public function testUnknownRoleBlocksWholeSet(): void
    {
        $result = (new AssignmentSelector())->selectSet(SelectionRule::decode(self::ruleBody()), self::input(['a'], ['a'], null), self::input(['a', 'b'], ['a', 'b'], null, 'clavium.locksmith'), null);
        self::assertSame('ROLE_SELECTION_REFUSED', $result->status); self::assertNull($result->courtthane); self::assertNull($result->locksmith);
    }

    public function testSwappedRolesRefuse(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new AssignmentSelector())->selectSet(SelectionRule::decode(self::ruleBody()), self::input([], [], null, 'clavium.locksmith'), self::input([], [], null), null);
    }

    public static function badCandidates(): iterable
    {
        $value = ['ref' => self::ref('a'), 'identity' => self::candidate('a')->identity];
        yield 'extra field' => [$value + ['authority' => true]];
        $bad = $value; unset($bad['identity']['model_version']); yield 'missing identity' => [$bad];
        $bad = $value; $bad['identity']['model_id'] = 42; yield 'numeric model' => [$bad];
        $bad = $value; $bad['identity']['binding_digest'] = 'sha256:'.str_repeat('0', 64); yield 'digest mismatch' => [$bad];
        $bad = $value; $bad['ref']['extra'] = true; yield 'extra ref key' => [$bad];
        $bad = $value; $bad['identity']['profile_digest'] = []; yield 'wrong digest type' => [$bad];
    }

    #[DataProvider('badCandidates')]
    public function testMalformedIdentityRefuses(mixed $value): void
    {
        $this->expectException(\InvalidArgumentException::class); Candidate::decode($value);
    }

    public function testMixedProfilesRefuse(): void
    {
        $b = self::candidate('b'); $identity = $b->identity; $identity['profile_digest'] = 'sha256:'.str_repeat('c', 64);
        $different = Candidate::decode(['ref' => self::ref('b'), 'identity' => $identity]);
        $this->expectException(\InvalidArgumentException::class); $this->expectExceptionMessage('MIXED_PROFILES');
        RoleInput::fromValidatedInputs('courtyard.courtthane', [self::candidate('a'), $different], [], self::order(null), []);
    }

    public function testDuplicatePermissionRefuses(): void
    {
        $this->expectException(\InvalidArgumentException::class); self::input(['a'], ['a', 'a'], null);
    }

    public function testUnknownOrderCannotNameUnfrozenEvidence(): void
    {
        $order = CapacityOrder::decode(['kind' => 'unknown', 'reason' => 'Incomplete', 'evidence_refs' => [self::ref('other')]]);
        $this->expectException(\InvalidArgumentException::class);
        RoleInput::fromValidatedInputs('courtyard.courtthane', [], [], $order, []);
    }

    public function testMalformedPairCannotBeIgnoredWhenRolesRefuse(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new AssignmentSelector())->selectSet(SelectionRule::decode(self::ruleBody()), self::input([], [], null), self::input([], [], null, 'clavium.locksmith'), [['courtthane' => RecordRef::decode(self::ref('a'))]]);
    }

    public function testOutputCarriesNoEffectOrAuthorityAndCoreIsExcluded(): void
    {
        $result = (new AssignmentSelector())->select(SelectionRule::decode(self::ruleBody()), self::input(['a'], ['a'], null));
        self::assertSame(['role', 'status', 'selected', 'tierIndex', 'eligible'], array_keys(get_object_vars($result)));
        $root = dirname(__DIR__, 3);
        foreach (glob($root.'/src/Imperium/Runtime/Onboarding/Selection/*.php') as $source) {
            $class = 'App\\Imperium\\Runtime\\Onboarding\\Selection\\'.basename($source, '.php');
            self::assertCount(1, (new \ReflectionClass($class))->getAttributes(\Symfony\Component\DependencyInjection\Attribute\Exclude::class), $class);
        }
        self::assertSame('ce13733ba4f5581517bcb42b0c752837616eb270981f3c95d4a89674660fe824', hash_file('sha256', $root.'/config/services.yaml'));
        $body = json_decode(file_get_contents($root.'/docs/provider-onboarding/o0-assignment-selection-rule.json'), true, 512, JSON_THROW_ON_ERROR)['runtime_body'];
        self::assertEquals(SelectionRule::decode($body), SelectionRule::decode(self::ruleBody()));
    }
}
