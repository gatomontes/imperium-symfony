<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class CorridorDispositionPrincipalAuthorityRemediationPreparationBatch0Test extends TestCase
{
    public function testInventoryClassifiesTheExactAuthorityGapAndReturnGate(): void
    {
        $root = dirname(__DIR__, 3);
        $inventory = preg_replace('/\s+/', ' ', (string) file_get_contents($root.'/docs/corridor-disposition-principal-authority-remediation-preparation-inventory.md'));
        foreach (['PREPARATION_BATCH_0_COMPLETE', 'Competent scope-grant owner', 'EXISTS_CANONICALLY', 'Corridor-disposition scope on current generation', 'Scope-grant authority', 'Scope-changing successor route', 'Mechanical successor producer', 'ABSENT', 'Caller-authority custody/store', 'Replay and contention', 'EXISTS_FRAGMENTED', 'Reconsideration Batch 5 return gate', 'DEFERRED_BOUNDARY', 'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE'] as $finding) self::assertNotFalse(stripos($inventory, $finding), $finding);
        foreach (['current principal cannot widen itself', 'authority_scope_changed=false', 'MasterMason is not the scope owner', 'test fixture', 'creates no authority'] as $nonAuthority) self::assertNotFalse(stripos($inventory, $nonAuthority), $nonAuthority);
    }

    public function testHandoffAuthorizesContractsOnlyAndPreservesTheClosedPerimeter(): void
    {
        $root = dirname(__DIR__, 3);
        $handoff = preg_replace('/\s+/', ' ', (string) file_get_contents($root.'/docs/handoffs/corridor-disposition-principal-authority-remediation-campaign-ready.md'));
        foreach (['Only Batch 1 is authorized', 'authority-empty', 'Operator Root corridor-scope grant', 'scope-bearing successor principal transition', 'corridor caller-authority issuance', 'Do not implement validators, stores, producers, issuers, consumers, reconstruction or runtime behavior', 'may not identify a live operator', 'activate a principal', 'seal a disposition', 'external I/O', 'Iron Gate', 'Lazaretto', 'Provider Execution Assurance remains paused'] as $boundary) self::assertNotFalse(stripos($handoff, $boundary), $boundary);
    }
}
