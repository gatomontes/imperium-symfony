<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationPrincipalProvenancePreparationBatch0Test extends TestCase
{
    public function testInventoryClassifiesEveryRequiredPrincipalBoundary(): void
    {
        $inventory = (string) file_get_contents(dirname(__DIR__, 3).'/docs/provider-binding-activation-principal-provenance-remediation-preparation-inventory.md');
        foreach (['EXISTS_CANONICALLY', 'EXISTS_FRAGMENTED', 'ABSENT', 'DEFERRED_BOUNDARY', 'Competent constituting authority', 'Mechanical principal producer', 'Source installation authority', 'Initial-instance constitution', 'Existing-instance remediation authority', 'Lifecycle state machine', 'Caller-authority issuance replay', 'Contention', 'Crash recovery', 'Read-only reconstruction', 'Suspension and revocation', 'Historical-principal interpretation', 'Secret and credential exclusion'] as $proof) {
            self::assertNotFalse(stripos($inventory, $proof), $proof);
        }
    }

    public function testPreparationAuthorizesContractsOnly(): void
    {
        $handoff = (string) file_get_contents(dirname(__DIR__, 3).'/docs/handoffs/provider-binding-activation-principal-provenance-remediation-preparation-batch-0-complete.md');
        foreach (['Only Batch 1 is authorized', 'three separately versioned', 'authority-empty contracts', 'may not implement an authority producer', 'principal producer', 'install or mutate a principal', 'issue caller authority', 'reconsider corridor disposition', 'external I/O', 'Iron Gate', 'Lazaretto', 'Provider Execution Assurance remains paused'] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }
}
