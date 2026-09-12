<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Ledger;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\AuthorityStore;
/** Prepared from current owned state, never an ingress-supplied operation or cached plan. */
interface ContextualPreparedOperation extends PreparedOperation
{
    public function prepareCurrent(AuthorityStore $store,array $state,array $policy,array $step,array $terms):array;
}
