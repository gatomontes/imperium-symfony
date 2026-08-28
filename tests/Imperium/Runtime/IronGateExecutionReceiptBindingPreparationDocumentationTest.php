<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class IronGateExecutionReceiptBindingPreparationDocumentationTest extends TestCase
{
    public function testBatchZeroInventoriesTheCompleteBoundaryAndKeepsImplementationClosed(): void
    {
        $root = dirname(__DIR__, 3);
        $inventory = (string) file_get_contents($root.'/docs/iron-gate-execution-receipt-binding-preparation-inventory.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/iron-gate-execution-receipt-binding-preparation-batch-0-complete.md');

        foreach (['`EXISTS_CANONICALLY`', '`EXISTS_FRAGMENTED`', '`ABSENT`', '`DEFERRED_BOUNDARY`'] as $classification) {
            self::assertStringContainsString($classification, $inventory);
        }
        foreach (['`DURABLE_RECEIPT_BOUND`', '`PRE_IO_UNCLAIMED`', '`UNKNOWN_OUTCOME_UNSAFE`', '`RECEIPT_RECOVERY_INCOMPLETE`', '`JOURNALED_EFFECT_FRAGMENTED`', '`DEFERRED_COMPETING_BOUNDARY`'] as $posture) {
            self::assertStringContainsString($posture, $inventory);
        }
        foreach ([
            'all 39 PHP files',
            'The five source files constructing `OutboundRequest`',
            'AgentMailEmailSendCommand',
            'OracleResearchCommissionService',
            'DeterministicBoundaryExecutor',
            'BrokeredSortieCognitionProviderInvoker',
            'HttpGetSortieToolExecutor',
            'OracleResearchEvidenceAdmissionService',
            'No inventoried consumer qualifies as `DURABLE_RECEIPT_BOUND`.',
            'During/after provider effect, before response',
            'No step is authorized by this inventory.',
            'internal rollback fiction.',
        ] as $proof) {
            self::assertStringContainsString($proof, $inventory.$handoff);
        }
        foreach (['Iron Gate', 'Lazaretto', 'sortie', 'credential-platform', 'revocation', 'propagation', 'telemetry', 'reassessment', 'containment', 'incident'] as $boundary) {
            self::assertStringContainsStringIgnoringCase($boundary, $inventory.$handoff);
        }

        self::assertStringContainsString('Runtime behavior is unchanged.', $handoff);
        self::assertStringContainsString('Batch 1 is not authorized by this handoff', $handoff);
    }
}
