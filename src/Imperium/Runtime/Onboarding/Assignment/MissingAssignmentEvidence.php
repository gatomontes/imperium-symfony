<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Assignment;
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class MissingAssignmentEvidence implements AssignmentEvidence
{
    public function verify(array $policy,array $assignments,array $originals,array $responses):void
    { throw new \RuntimeException('O4_CURRENT_ASSIGNMENT_EVIDENCE_MISSING'); }
}
