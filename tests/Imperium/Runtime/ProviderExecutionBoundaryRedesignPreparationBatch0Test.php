<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderExecutionBoundaryRedesignPreparationBatch0Test extends TestCase
{
    public function testInventorySeparatesCredentialAuthorityCapabilityAndExecutor(): void
    {
        $inventory = (string) file_get_contents(dirname(__DIR__, 3).'/docs/provider-execution-boundary-redesign-preparation-inventory.md');

        foreach ([
            'EXISTS_CANONICALLY',
            'EXISTS_FRAGMENTED',
            'ABSENT',
            'DEFERRED_BOUNDARY',
            'Provider credential possession',
            'Durable execution-authority identity',
            'Process-local `CredentialCapability` identity',
            'Credential-owning execution boundary',
            'Exact executor principal',
            'Single-operation provider-binding activation',
            'Atomic authority consumption',
            'Effect-start ordering before credential resolution',
            'Crash and replay matrix',
            'Explicit non-authorities',
            'same-process governed executor',
            'TRUSTED_WRITER_CANONICAL_INTEGRITY',
            'UNKNOWN_REPLAY_PROHIBITED',
        ] as $proof) {
            self::assertNotFalse(stripos($inventory, $proof), $proof);
        }
    }

    public function testPreparationPreservesClosedRuntimePerimeter(): void
    {
        $root = dirname(__DIR__, 3);
        $inventory = preg_replace('/\s+/', ' ', (string) file_get_contents($root.'/docs/provider-execution-boundary-redesign-preparation-inventory.md'));
        $handoff = preg_replace('/\s+/', ' ', (string) file_get_contents($root.'/docs/handoffs/provider-execution-boundary-redesign-preparation-batch-0-complete.md'));

        foreach ([
            'Batch 1 is not begun or authorized',
            'No runtime contract was defined',
            'runtime behavior is unchanged',
            'No principal or binding was activated',
            'no authority was issued or consumed',
            'no credential or capability was issued, transferred, resolved, persisted, reconstructed or otherwise handled',
            'no provider was invoked',
            'no external I/O occurred',
            'no live command was migrated',
            'Iron Gate',
            'Lazaretto',
        ] as $boundary) {
            self::assertNotFalse(stripos($inventory.$handoff, $boundary), $boundary);
        }
    }
}
