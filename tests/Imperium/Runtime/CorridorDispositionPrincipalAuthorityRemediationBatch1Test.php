<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\ActivationCorridorDispositionCallerAuthorityIssuanceAuthorizationContract;
use App\Imperium\Runtime\Imperator\ImperatorCorridorDispositionScopeGrantContract;
use App\Imperium\Runtime\Imperator\ImperatorCorridorDispositionScopeSuccessorContract;
use PHPUnit\Framework\TestCase;

final class CorridorDispositionPrincipalAuthorityRemediationBatch1Test extends TestCase
{
    public function testContractsSeparateScopeGrantSuccessorActivationAndCallerIssuance(): void
    {
        self::assertSame('AUTHORIZE_EXACT_CORRIDOR_SCOPE_SUCCESSOR', ImperatorCorridorDispositionScopeGrantContract::PERMITTED_TRANSITION);
        self::assertSame(['corridor_disposition_authority'], ImperatorCorridorDispositionScopeGrantContract::REQUIRED_SCOPE_DELTA_FIELDS);
        self::assertSame('COMMIT_EXACT_CORRIDOR_SCOPE_SUCCESSOR', ImperatorCorridorDispositionScopeSuccessorContract::PERMITTED_TRANSITION);
        self::assertSame('PENDING_ACTIVATION', ImperatorCorridorDispositionScopeSuccessorContract::INITIAL_STATUS);
        self::assertContains('separate_activation_authority_required', ImperatorCorridorDispositionScopeSuccessorContract::REQUIRED_FIELDS);
        self::assertSame('ISSUE_EXACT_ACTIVATION_CORRIDOR_DISPOSITION_CALLER_AUTHORITY', ActivationCorridorDispositionCallerAuthorityIssuanceAuthorizationContract::PERMITTED_TRANSITION);
        self::assertSame(['QUARANTINED_PENDING_REMEDIATION', 'RETIRE_CORRIDOR'], ActivationCorridorDispositionCallerAuthorityIssuanceAuthorizationContract::DISPOSITIONS);
        self::assertSame('REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE', ActivationCorridorDispositionCallerAuthorityIssuanceAuthorizationContract::CONTINUING_CUSTODY_REFUSAL);
        foreach ([ImperatorCorridorDispositionScopeGrantContract::NON_AUTHORITIES, ImperatorCorridorDispositionScopeSuccessorContract::NON_AUTHORITIES, ActivationCorridorDispositionCallerAuthorityIssuanceAuthorizationContract::NON_AUTHORITIES] as $claims) foreach ($claims as $claim) self::assertFalse($claim);
    }

    public function testDocumentationAuthorizesOnlyValidatorsAndFixtureStoresNext(): void
    {
        $root = dirname(__DIR__, 3);
        $contract = preg_replace('/\s+/', ' ', (string) file_get_contents($root.'/docs/corridor-disposition-principal-authority-remediation-contracts.md'));
        $handoff = preg_replace('/\s+/', ' ', (string) file_get_contents($root.'/docs/handoffs/corridor-disposition-principal-authority-remediation-batch-1-complete.md'));
        foreach (['BATCH_1_AUTHORITY_EMPTY_SCOPE_SUCCESSOR_AND_ISSUANCE_CONTRACTS_COMPLETE', 'only `corridor_disposition_authority=true`', 'PENDING_ACTIVATION', 'separate activation authority', 'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE', 'do not satisfy the Reconsideration Batch 5 return gate'] as $claim) self::assertNotFalse(stripos($contract, $claim), $claim);
        foreach (['Only remediation Batch 2 is authorized', 'fail-closed validators', 'immutable fixture stores', 'may not identify a live Operator Root', 'issue or consume authority', 'activate a principal', 'implement a producer, issuer, consumer or current-state registry', 'seal a disposition', 'external I/O', 'Iron Gate', 'Lazaretto', 'Provider Execution Assurance remains paused'] as $boundary) self::assertNotFalse(stripos($handoff, $boundary), $boundary);
    }
}
