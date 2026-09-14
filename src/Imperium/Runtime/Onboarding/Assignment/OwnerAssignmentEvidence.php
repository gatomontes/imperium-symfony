<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Assignment;

use App\Imperium\Runtime\Citadel\Formation\FormationOwnerFrame;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\AuthorityStore;

/** Internal specialization; a live frame is synchronization, never authority. */
interface OwnerAssignmentEvidence extends AssignmentEvidence
{
    public function verifyInOwner(AuthorityStore $store, FormationOwnerFrame $owner,
        array $policy, array $assignments, array $originals, array $responses): void;
}
