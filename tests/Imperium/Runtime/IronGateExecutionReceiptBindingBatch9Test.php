<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\DeterministicRawProviderResultContract;
use PHPUnit\Framework\TestCase;

final class IronGateExecutionReceiptBindingBatch9Test extends TestCase
{
    public function testRawResultContractAndDocumentationKeepAdmissionClosed(): void
    {
        self::assertSame('imperium.la-cortine.deterministic-raw-provider-result/v2', DeterministicRawProviderResultContract::SCHEMA);
        foreach (['provider_response_envelope', 'provider_invocation_admission', 'execution_claim', 'provider_outcome', 'raw_receipt', 'recovery'] as $field) {
            self::assertContains($field, DeterministicRawProviderResultContract::REQUIRED_FIELDS);
        }

        $root = dirname(__DIR__, 3);
        $boundary = (string) file_get_contents($root.'/docs/iron-gate-raw-provider-result.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/iron-gate-execution-receipt-binding-batch-9-complete.md');
        foreach (['`BATCH_9_RAW_PROVIDER_RECEIPT_AND_OUTCOME_DURABLE`', '`ACCEPTED`', '`REJECTED`', '`UNKNOWN_REPLAY_PROHIBITED`', '`RAW_RECEIPT_SEALED`', '`provider_reinvoked=false`'] as $proof) {
            self::assertStringContainsString($proof, $boundary);
        }
        self::assertStringContainsString('Batch 10 is not', $handoff);
        self::assertStringContainsString('No provider was invoked', $handoff);
    }
}
