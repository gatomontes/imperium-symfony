<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationCorridorDispositionReconsiderationPreparationBatch0Test extends TestCase
{
    public function testInventoryClassifiesEveryRequiredDispositionBoundary(): void
    {
        $inventory = (string) file_get_contents(dirname(__DIR__, 3).'/docs/provider-binding-activation-corridor-disposition-reconsideration-preparation-inventory.md');

        foreach (['EXISTS_CANONICALLY', 'EXISTS_FRAGMENTED', 'ABSENT', 'DEFERRED_BOUNDARY', 'Instance-specific competent active principal', 'Corridor-disposition caller authority', 'Caller-authority custody and single-winner consumption', 'Eligible evidence dossier', 'QUARANTINED_PENDING_REMEDIATION', 'RETIRE_CORRIDOR', 'Disposition replay', 'Disposition contention', 'Principal expiry and revocation', 'Crash recovery', 'Read-only reconstruction', 'Candidate-disposition producer', 'Recovery owner', 'Non-authorities', 'Provider Execution Assurance reconsideration evidence'] as $proof) {
            self::assertNotFalse(stripos($inventory, $proof), $proof);
        }
    }

    public function testPreparationPreservesTheCustodyRefusalAndOperationalPerimeter(): void
    {
        $root = dirname(__DIR__, 3);
        $inventory = (string) file_get_contents($root.'/docs/provider-binding-activation-corridor-disposition-reconsideration-preparation-inventory.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/provider-binding-activation-corridor-disposition-reconsideration-preparation-batch-0-complete.md');

        foreach (['REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE', 'Neither candidate', 'presently eligible to be sealed', 'Only Batch 1 may next be considered', 'does not authorize Batch 1', 'No runtime contract was defined', 'runtime behavior is unchanged', 'No principal or binding was activated', 'no caller authority was issued or consumed', 'no disposition was sealed', 'no activation artifact was mutated', 'no credential or capability was handled', 'no provider was invoked', 'external I/O', 'Iron Gate', 'Lazaretto', 'Provider Execution Assurance remains paused'] as $boundary) {
            self::assertNotFalse(stripos($inventory.$handoff, $boundary), $boundary);
        }
    }
}
