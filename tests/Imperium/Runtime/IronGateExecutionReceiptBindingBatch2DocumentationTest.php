<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class IronGateExecutionReceiptBindingBatch2DocumentationTest extends TestCase
{
    public function testBatchTwoRefusesConsumerMigrationWhenBothPrerequisitesAreAbsent(): void
    {
        $root = dirname(__DIR__, 3);
        $assessment = (string) file_get_contents($root.'/docs/iron-gate-agentmail-provider-safety-assessment.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/iron-gate-execution-receipt-binding-batch-2-complete.md');

        foreach ([
            '`BATCH_2_PROVIDER_SAFETY_NOT_PROVED_NO_ELIGIBLE_CONSUMER`',
            '`BLOCKED_SOURCE_AUTHORITY_AND_PROVIDER_SAFETY`',
            '`INELIGIBLE_AS_OUTBOUND_SOURCE_AUTHORITY`',
            '`NON_REPLAYABLE_UNKNOWN_OUTCOME_REQUIRED`',
            '`UNKNOWN_REPLAY_PROHIBITED`',
            'no deterministic consumer is eligible for migration',
            'performs no provider call',
            'https://docs.agentmail.to/knowledge-base/preventing-duplicate-sends',
        ] as $invariant) {
            self::assertStringContainsString($invariant, $assessment);
        }

        self::assertStringContainsString('Curia\'s existing bounded execution authorization cannot fill the gap', $handoff);
        self::assertStringContainsString('No consumer-adoption batch is open.', $handoff);
        self::assertStringContainsString('Batch 3 is not authorized', $handoff);
        self::assertStringContainsString('Runtime behavior is unchanged and no external I/O occurred.', $handoff);
    }
}
