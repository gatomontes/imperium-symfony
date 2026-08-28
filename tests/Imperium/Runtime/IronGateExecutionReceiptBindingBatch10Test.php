<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\DeterministicReceiptBindingContract;
use PHPUnit\Framework\TestCase;

final class IronGateExecutionReceiptBindingBatch10Test extends TestCase
{
    public function testBindingContractAndDocumentationKeepAdmissionExactAndReconstructionReadOnly(): void
    {
        self::assertSame('imperium.la-cortine.deterministic-receipt-binding/v1', DeterministicReceiptBindingContract::SCHEMA);
        self::assertTrue(DeterministicReceiptBindingContract::RECONSTRUCTION_REQUIREMENTS['forward_recovery_never_reinvokes_provider']);
        self::assertFalse(DeterministicReceiptBindingContract::CONTRACT_BOUNDARY['expands_lazaretto_policy']);
        self::assertFalse(DeterministicReceiptBindingContract::CONTRACT_BOUNDARY['performs_external_io']);

        $root = dirname(__DIR__, 3);
        $boundary = (string) file_get_contents($root.'/docs/iron-gate-lazaretto-receipt-binding-and-reconstruction.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/iron-gate-execution-receipt-binding-batch-10-complete.md');
        foreach (['`BATCH_10_ACCEPTED_RECEIPT_BOUND_AND_RECONSTRUCTIBLE`', '`agentmail.message/v1`', '`provider_reinvoked=false`', '`credential_resolved=false`', '`external_io_performed=false`', 'Rejected results', 'Unknown outcomes'] as $proof) {
            self::assertStringContainsString($proof, $boundary);
        }
        self::assertStringContainsString('Batch 11 is not', $handoff);
        self::assertStringContainsString('Existing Lazaretto', $handoff);
    }
}
