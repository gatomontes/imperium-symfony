<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\DeterministicProviderInvocationAdmissionContract;
use PHPUnit\Framework\TestCase;

final class IronGateExecutionReceiptBindingBatch8Test extends TestCase
{
    public function testAdmissionContractAndDocumentationKeepLiveIoAndReceiptsClosed(): void
    {
        self::assertSame('imperium.la-cortine.deterministic-provider-invocation-admission/v1', DeterministicProviderInvocationAdmissionContract::SCHEMA);
        foreach (['effect_start_journal', 'execution_claim', 'credential_use', 'provider_request'] as $field) {
            self::assertContains($field, DeterministicProviderInvocationAdmissionContract::REQUIRED_FIELDS);
        }

        $root = dirname(__DIR__, 3);
        $boundary = (string) file_get_contents($root.'/docs/iron-gate-journal-bound-agentmail-invocation.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/iron-gate-execution-receipt-binding-batch-8-complete.md');
        foreach (['`BATCH_8_JOURNAL_GATED_PROVIDER_CALLBACK_NO_LIVE_IO`', '`credential_use_committed=true`', '`provider_callback_may_have_run=true`', '`UNKNOWN_REPLAY_PROHIBITED`', 'No live network request'] as $proof) {
            self::assertStringContainsString($proof, $boundary);
        }
        self::assertStringContainsString('Batch 9 is not', $handoff);
        self::assertStringContainsString('No live external I/O occurred', $handoff);
    }
}
