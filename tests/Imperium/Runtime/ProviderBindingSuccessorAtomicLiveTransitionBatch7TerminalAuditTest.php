<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorAtomicLiveTransitionBatch7TerminalAuditTest extends TestCase
{
    public function testTerminalAuditRetainsTheCompleteEvidenceChain(): void
    {
        $document = $this->document('docs/provider-binding-successor-atomic-live-transition-batch-7-terminal-audit.md');
        foreach ([
            'BATCH_7_TERMINAL_AUDIT_PASSED_ATOMIC_LIVE_TRANSITION_EVIDENCE_COMPLETE',
            'PREPARATION_BATCH_0_COMPLETE_ATOMIC_LIVE_TRANSITION_EXECUTION_BOUNDARIES_CLASSIFIED',
            'BATCH_1_AUTHORITY_EMPTY_TRANSITION_DECISION_INPUT_PRODUCER_AND_RESULT_CONTRACTS_COMPLETE',
            'BATCH_2_AUTHORITY_EMPTY_TRANSITION_AUTHORITY_ISSUANCE_CUSTODY_AND_DELIVERY_CONTRACTS_COMPLETE',
            'BATCH_3_INERT_EXACT_ROOT_JOURNAL_LOCK_WRITESET_RECOVERY_WINNER_AND_RECEIPT_CONTRACTS_COMPLETE',
            'BATCH_4_DISPOSABLE_INTERRUPTION_CONTENTION_REPLAY_PARTIAL_WRITE_AND_RECOVERY_CLASSIFICATION_PROOF_COMPLETE',
            'BATCH_5_READ_ONLY_RECOVERY_PLAN_AND_AGGREGATE_RECONSTRUCTION_COMPLETE',
            'BATCH_6_READ_ONLY_ADVERSARIAL_RECOVERY_AND_RECONSTRUCTION_AUDIT_COMPLETE',
            'It does not prove an executable atomic transition.',
        ] as $finding) {
            self::assertStringContainsString($finding, $document);
        }
    }

    public function testCampaignClosurePreservesTheClosedRuntimePerimeter(): void
    {
        $document = $this->document('docs/handoffs/provider-binding-successor-atomic-live-transition-campaign-complete.md');
        foreach ([
            'PROVIDER_BINDING_SUCCESSOR_ATOMIC_LIVE_TRANSITION_CAMPAIGN_COMPLETE_PRE_EXECUTION_ONLY',
            'The provider binding remains `BOUND_INACTIVE`.',
            'The required v3 execution admission remains `NOT_IMPLEMENTED`.',
            'No atomic-live-transition batch remains.',
            'may not persist a journal or acquire a live lock',
            'may not issue or consume authority',
            'may not admit execution or adopt a successor',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider or perform external I/O',
            'may not open Iron Gate or Lazaretto',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $document);
        }
        self::assertStringNotContainsString('BOUND_ACTIVE', $document);
    }

    public function testExecutionRequiresASeparatelySelectedCampaign(): void
    {
        $required = 'A separate explicitly selected campaign is required before executable atomic consumption, v3 admission, successor adoption or binding transition may be considered.';
        self::assertStringContainsString($required, $this->document('docs/provider-binding-successor-atomic-live-transition-batch-7-terminal-audit.md'));
        self::assertStringContainsString($required, $this->document('docs/handoffs/provider-binding-successor-atomic-live-transition-campaign-complete.md'));
    }

    public function testLedgerAndMissionFlowRecordTheTerminalPosture(): void
    {
        self::assertStringContainsString('Provider Binding Successor Atomic Live Transition Batch 6', $this->document('docs/deferred-local-test-ledger.md'));
        $flow = $this->document('docs/delegate-mission-flow.md');
        self::assertStringContainsString('BATCH_7_TERMINAL_AUDIT_PASSED_ATOMIC_LIVE_TRANSITION_EVIDENCE_COMPLETE', $flow);
        self::assertStringContainsString('PROVIDER_BINDING_SUCCESSOR_ATOMIC_LIVE_TRANSITION_CAMPAIGN_COMPLETE_PRE_EXECUTION_ONLY', $flow);
    }

    private function document(string $path): string
    {
        $contents = file_get_contents(dirname(__DIR__, 3).'/'.$path);
        self::assertNotFalse($contents);

        return $contents;
    }
}
