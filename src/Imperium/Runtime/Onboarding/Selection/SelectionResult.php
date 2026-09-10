<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Onboarding\Selection;

/** Pure calculation data. Not an act, receipt, assignment or permission. */
final readonly class SelectionResult
{
    private function __construct(public string $role, public string $status, public ?Candidate $selected, public ?int $tierIndex, public array $eligible) {}

    public static function refused(string $role, string $status): self
    {
        if (!in_array($status, ['INVALID_CAPACITY_ORDER', 'NO_ELIGIBLE_ASSIGNMENT', 'CAPACITY_ORDER_UNKNOWN'], true)) { throw new \InvalidArgumentException('INVALID_REFUSAL'); }
        return new self($role, $status, null, null, []);
    }

    public static function selected(string $role, Candidate $selected, ?int $tierIndex, array $eligible): self
    {
        return new self($role, 'SELECTED', $selected, $tierIndex, $eligible);
    }
}
