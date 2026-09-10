<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Onboarding\Selection;

/** Exact v1 runtime body. The public preparation wrapper is not accepted. */
final readonly class SelectionRule
{
    public const IDENTITY_FIELDS = ['provider', 'model_id', 'model_version', 'configuration_digest', 'profile_digest', 'binding_digest'];
    private function __construct() {}

    public static function decode(mixed $value): self
    {
        $v = Shape::object($value, ['algorithm', 'capacity_basis', 'even_rule', 'same_tier_tie_break', 'unknown_behavior', 'group_roles']);
        $roles = Shape::object($v['group_roles'], ['W2', 'W3']);
        if ($v['algorithm'] !== 'role_capacity_middle_tier_v1'
            || $v['capacity_basis'] !== 'augur_profile_specific_evidence_assessment'
            || $v['even_rule'] !== 'lower_middle'
            || $v['same_tier_tie_break'] !== self::IDENTITY_FIELDS
            || $v['unknown_behavior'] !== 'refuse_when_multiple_eligible_candidates'
            || $roles['W2'] !== 'courtyard.courtthane' || $roles['W3'] !== 'clavium.locksmith') {
            throw new \InvalidArgumentException('INVALID_SELECTION_RULE');
        }
        return new self();
    }
}
