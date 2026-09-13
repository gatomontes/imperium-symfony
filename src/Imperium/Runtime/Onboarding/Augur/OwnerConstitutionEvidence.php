<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Augur;

use App\Imperium\Runtime\Bootstrap\OperatorRootOwnership;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\AuthorityStore;

/** Internal integration only: the caller already owns the journal boundary.
 * No lock acquisition, dispatch, retained frame snapshot or new request field. */
interface OwnerConstitutionEvidence extends ConstitutionEvidence
{
    public function verifyInFrame(AuthorityStore $store,OperatorRootOwnership $owner,array $state,
        array $policy,array $constitution,array $originals,?array $holder):void;
}
