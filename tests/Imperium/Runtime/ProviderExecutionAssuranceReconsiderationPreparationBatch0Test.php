<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderExecutionAssuranceReconsiderationPreparationBatch0Test extends TestCase
{
    public function testInventoryClassifiesChangedEvidenceAndRemainingStopConditions(): void
    {
        $inventory = (string) file_get_contents(dirname(__DIR__, 3).'/docs/provider-execution-assurance-reconsideration-preparation-inventory.md');

        foreach (['EXISTS_CANONICALLY', 'EXISTS_FRAGMENTED', 'ABSENT', 'DEFERRED_BOUNDARY', 'Provider-neutral governed tool and provider separation', 'Competent active-principal provenance', 'Corridor caller-authority custody and consumption', 'Terminal corridor disposition', 'Provider-binding activation authority', 'Cross-process opaque capability custody', 'Crash recovery and reconstruction', 'Local replay and contention', 'Non-authorities', 'UNKNOWN_REPLAY_PROHIBITED', 'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE'] as $proof) {
            self::assertNotFalse(stripos($inventory, $proof), $proof);
        }
    }

    public function testPreparationRefusesToInferExecutionAuthority(): void
    {
        $root = dirname(__DIR__, 3);
        $inventory = preg_replace('/\s+/', ' ', (string) file_get_contents($root.'/docs/provider-execution-assurance-reconsideration-preparation-inventory.md'));
        $handoff = preg_replace('/\s+/', ' ', (string) file_get_contents($root.'/docs/handoffs/provider-execution-assurance-reconsideration-preparation-batch-0-complete.md'));

        foreach (['No Provider Execution Assurance Batch 1 is authorized', 'No runtime contract was defined', 'runtime behavior is unchanged', 'No principal or binding was activated', 'no authority was issued or consumed', 'no disposition was selected or sealed', 'no activation artifact was mutated', 'no credential or capability was handled', 'no provider was invoked', 'no external I/O occurred', 'Iron Gate', 'Lazaretto', 'Provider Execution Assurance remains paused'] as $boundary) {
            self::assertNotFalse(stripos($inventory.$handoff, $boundary), $boundary);
        }
    }
}
