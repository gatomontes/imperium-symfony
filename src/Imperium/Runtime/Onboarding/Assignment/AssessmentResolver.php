<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Assignment;

use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore,Rules as R,StrictJson};
use App\Imperium\Runtime\Onboarding\Augur\AugurAdapter;
use App\Imperium\Runtime\Onboarding\Ledger\{AssessmentGroups,LedgerState,ResponseEvidence,CompletionResolver};

/** Current original-backed evidence only. No assignment, permission or dispatch. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class AssessmentResolver
{
    public function __construct(private AuthorityStore $store, private AugurAdapter $adapter) {}

    /** No supplied-state ingress. The complete resolution observes one journal frame. */
    public function resolve(array $policyRef): array
    {
        R::ref($policyRef);
        return $this->store->journal->inspect(fn(array $frame):array => StrictJson::within(
            fn():array => $this->originals($frame['state'], $policyRef)
        ));
    }

    use OriginalAssessment;
    private function assessmentAdapter(): AugurAdapter { return $this->adapter; }
}
