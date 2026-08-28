<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class IronGateExecutionReceiptBindingBatch1DocumentationTest extends TestCase
{
    public function testBatchOneDefinesContractsWithoutMigratingAConsumer(): void
    {
        $root = dirname(__DIR__, 3);
        $contract = (string) file_get_contents($root.'/docs/iron-gate-execution-receipt-binding-contract.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/iron-gate-execution-receipt-binding-batch-1-complete.md');

        foreach ([
            '`BATCH_1_CONTRACTS_DEFINED_NO_CONSUMER_MIGRATED`',
            'imperium.la-cortine.deterministic-execution-claim/v1',
            'imperium.la-cortine.deterministic-receipt-binding/v1',
            '`PROVIDER_IDEMPOTENCY_KEY`',
            '`NON_REPLAYABLE_UNKNOWN_OUTCOME`',
            '`UNKNOWN_REPLAY_PROHIBITED`',
            'Network I/O',
            'Credential secret material is categorically excluded.',
            'Batch 1 migrates no issuer or consumer',
        ] as $invariant) {
            self::assertStringContainsString($invariant, $contract);
        }

        self::assertStringContainsString('They grant no authority and perform no transition.', $handoff);
        self::assertStringContainsString('Batch 2 is not authorized', $handoff);
        self::assertStringContainsString('AgentMail remains only a candidate', $handoff);
    }
}
