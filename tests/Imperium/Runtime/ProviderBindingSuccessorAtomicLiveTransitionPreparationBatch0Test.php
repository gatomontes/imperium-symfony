<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorAtomicLiveTransitionPreparationBatch0Test extends TestCase
{
    public function testInventoryClassifiesEveryExecutableTransitionBoundary(): void
    {
        $inventory = $this->document(
            'docs/provider-binding-successor-atomic-live-transition-preparation-inventory.md',
        );

        foreach ([
            'PREPARATION_BATCH_0_COMPLETE_ATOMIC_LIVE_TRANSITION_EXECUTION_BOUNDARIES_CLASSIFIED',
            'Exact runtime transition entry point',
            'Executable transition-decision producer',
            'Live authority issuer, custodian and process-local delivery',
            'Combined same-root winner contract and validator',
            'Exact transition lock scope and lock order',
            'Multi-record atomic commit and rollback',
            'Same-root authority consumption',
            'Exact BOUND_INACTIVE source and successor target',
            'First irreversible write',
            'Production crash recovery coordinator',
            'Durable transition receipt and audit lineage',
            'Process-local capability delivery',
            'Credential resolution, provider invocation, external I/O and effect start',
            'EXISTS_CANONICALLY',
            'EXISTS_FRAGMENTED',
            'ABSENT',
            'DEFERRED_BOUNDARY',
        ] as $finding) {
            self::assertStringContainsString($finding, $inventory);
        }
    }

    public function testInventoryDoesNotMistakeMutualExclusionForCrashAtomicity(): void
    {
        $inventory = $this->document(
            'docs/provider-binding-successor-atomic-live-transition-preparation-inventory.md',
        );

        foreach ([
            'individual atomic renames do not provide one crash-safe commit',
            'sole outer lock for the transition',
            'validate all inputs before the first irreversible write',
            'one recoverable unit',
            'Nested authority, immutable-directory and mutable state locks',
            'permit partial evidence or deadlock',
        ] as $finding) {
            self::assertStringContainsString($finding, $inventory);
        }
    }

    public function testHandoffAuthorizesDecisionContractsOnly(): void
    {
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-atomic-live-transition-preparation-batch-0-complete.md',
        );

        foreach ([
            'Only Provider Binding Successor Atomic Live Transition Batch 1 authority-empty transition-decision producer, exact-principal input and immutable result contracts with pure validation may next be considered.',
            'may define contracts and pure validators only',
            'may not produce a decision',
            'may not issue or consume live authority',
            'may not admit execution',
            'may not adopt a successor or change binding state',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'may not start a provider effect',
            'may not authorize retry',
            'may not migrate a live command',
            'may not open Iron Gate or Lazaretto',
            'The provider binding remains BOUND_INACTIVE.',
            'The v3 execution admission remains NOT_IMPLEMENTED.',
            'UNKNOWN_REPLAY_PROHIBITED remains binding.',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    public function testPreparationExplicitlyChangesNoRuntimeSource(): void
    {
        self::assertStringContainsString(
            'It changes no runtime source.',
            $this->document(
                'docs/provider-binding-successor-atomic-live-transition-preparation-inventory.md',
            ),
        );
    }

    private function document(string $path): string
    {
        return (string) preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(dirname(__DIR__, 3).'/'.$path),
        );
    }
}
