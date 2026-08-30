<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderExecutionEffectReadinessPreparationBatch0Test extends TestCase
{
    public function testPreparationSeparatesEveryRemainingProviderEffectGate(): void
    {
        $repository = dirname(__DIR__, 3);
        $inventory = (string) file_get_contents(
            $repository.'/docs/provider-execution-effect-readiness-preparation-inventory.md',
        );
        $handoff = (string) file_get_contents(
            $repository.'/docs/handoffs/provider-execution-effect-readiness-preparation-batch-0-complete.md',
        );
        $documentation = (string) preg_replace('/\\s+/', ' ', $inventory.$handoff);

        foreach ([
            'PREPARATION_BATCH_0_COMPLETE_EFFECT_GATES_SEPARABLE_ASSURANCE_FIRST',
            'ATTESTED_INERT',
            'BOUND_INACTIVE',
            'Live-call runtime contract',
            'AgentMail provider-contract evidence',
            'UNKNOWN_REPLAY_PROHIBITED',
            'Provider Assurance Evidence Admission',
            'Principal and Binding Activation',
            'Sterile Provider Conformance',
            'Live-consumer adoption',
            'Iron Gate',
            'Lazaretto',
        ] as $required) {
            self::assertStringContainsString($required, $documentation);
        }
    }

    public function testPreparationPreservesTheAuthorityEmptyPerimeter(): void
    {
        $repository = dirname(__DIR__, 3);
        $next = (string) file_get_contents(
            $repository.'/docs/next-campaign-provider-execution-effect-readiness.md',
        );
        $handoff = (string) file_get_contents(
            $repository.'/docs/handoffs/provider-execution-effect-readiness-preparation-batch-0-complete.md',
        );
        $documentation = (string) preg_replace('/\\s+/', ' ', $next.$handoff);

        foreach ([
            'authority-empty Provider Assurance Evidence Admission contracts',
            'No runtime contract or behavior changed',
            'No principal or binding was activated',
            'no credential was handled',
            'no provider was invoked',
            'no external I/O occurred',
            'Iron Gate and Lazaretto remained closed',
        ] as $required) {
            self::assertStringContainsString($required, $documentation);
        }
    }

    public function testDeferredTerminalTestIsRecordedClearWithoutInventedCounts(): void
    {
        $ledger = (string) file_get_contents(
            dirname(__DIR__, 3).'/docs/deferred-local-test-ledger.md',
        );

        self::assertStringContainsString(
            'CLEAR_OPERATOR_REPORTED_AFTER_REPAIR',
            $ledger,
        );
        self::assertStringContainsString('Counts: not supplied', $ledger);
        self::assertMatchesRegularExpression('/## Pending\\s+None\\./', $ledger);
    }
}
