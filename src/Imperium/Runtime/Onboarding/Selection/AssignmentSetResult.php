<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Onboarding\Selection;

/** Pure pair result; a refused result never exposes a partial selection. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class AssignmentSetResult
{
    public function __construct(public string $status, public ?SelectionResult $courtthane, public ?SelectionResult $locksmith)
    {
        if (!in_array($status, ['SELECTED', 'ROLE_SELECTION_REFUSED', 'SELECTED_ASSIGNMENT_SET_NOT_PERMITTED'], true)
            || ($status === 'SELECTED' && ($courtthane?->selected === null || $locksmith?->selected === null))
            || ($status !== 'SELECTED' && ($courtthane !== null || $locksmith !== null))) {
            throw new \InvalidArgumentException('INVALID_SET_RESULT');
        }
    }
}
