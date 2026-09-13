<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Assignment;

/** Fixed deployment port, never request data. Must verify substantive Profile
 * predicates and current exact binding/Profile generations against originals.
 * It must not dispatch, acquire the journal lock, or substitute any tuple. */
interface AssignmentEvidence
{
    public function verify(array $policy,array $assignments,array $originals,array $responses):void;
}
