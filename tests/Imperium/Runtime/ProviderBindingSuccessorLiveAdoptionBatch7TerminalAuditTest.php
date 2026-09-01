<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorLiveAdoptionBatch7TerminalAuditTest extends TestCase
{
    public function testTerminalAuditRetainsTheCompleteProofChain(): void
    {
        $terminal = $this->document(
            'docs/provider-binding-successor-live-adoption-batch-7-terminal-audit.md',
        );

        foreach ([
            'PREPARATION_BATCH_0_COMPLETE_LIVE_ADOPTION_DECISION_AUTHORITY_AND_ATOMIC_V3_TRANSITION_REQUIRED',
            'BATCH_1_AUTHORITY_EMPTY_LIVE_ADOPTION_DECISION_PRINCIPAL_AND_ISSUER_CONTRACTS_COMPLETE',
            'BATCH_2_AUTHORITY_EMPTY_LIVE_ADOPTION_ISSUANCE_AND_DURABLE_CUSTODY_CONTRACTS_COMPLETE',
            'BATCH_3_INERT_SAME_ROOT_V3_ADMISSION_CONSUMPTION_ADOPTION_AND_BINDING_BOUNDARY_COMPLETE',
            'BATCH_4_DISPOSABLE_INTERRUPTION_REPLAY_CONTENTION_EXPIRY_AND_REVOCATION_PROOF_COMPLETE',
            'BATCH_5_READ_ONLY_LIVE_ADOPTION_AGGREGATE_RECONSTRUCTION_COMPLETE',
            'BATCH_6_READ_ONLY_LIVE_ADOPTION_ADVERSARIAL_READINESS_AUDIT_PASSED',
        ] as $result) {
            self::assertStringContainsString($result, $terminal);
        }
    }

    public function testTerminalDispositionClosesReadinessWithoutInventingTransition(): void
    {
        $terminal = $this->document(
            'docs/provider-binding-successor-live-adoption-batch-7-terminal-audit.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-live-adoption-campaign-complete.md',
        );

        foreach ([
            'BATCH_7_TERMINAL_AUDIT_PASSED_PROVIDER_BINDING_SUCCESSOR_LIVE_ADOPTION_READINESS_COMPLETE',
            'PROVIDER_BINDING_SUCCESSOR_LIVE_ADOPTION_CAMPAIGN_COMPLETE_PRE_LIVE_TRANSITION_ONLY',
            'No live-adoption readiness batch remains.',
            'The provider binding remains BOUND_INACTIVE.',
            'The v3 execution admission remains NOT_IMPLEMENTED.',
            'UNKNOWN_REPLAY_PROHIBITED remains binding.',
        ] as $finding) {
            self::assertStringContainsString($finding, $terminal);
            self::assertStringContainsString($finding, $handoff);
        }

        self::assertStringNotContainsString('BOUND_ACTIVE', $terminal);
        self::assertStringNotContainsString('BOUND_ACTIVE', $handoff);
    }

    public function testClosureRequiresSeparateSelectionBeforeRuntimeTransition(): void
    {
        $required = 'A separate explicitly selected campaign is required before any live authority consumption, execution admission, successor adoption, binding-state transition, credential or capability handling, provider invocation, external I/O or provider effect may be considered.';

        self::assertStringContainsString(
            $required,
            $this->document(
                'docs/provider-binding-successor-live-adoption-batch-7-terminal-audit.md',
            ),
        );
        self::assertStringContainsString(
            $required,
            $this->document(
                'docs/handoffs/provider-binding-successor-live-adoption-campaign-complete.md',
            ),
        );
    }

    public function testClosurePreservesEveryNonAuthority(): void
    {
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-live-adoption-campaign-complete.md',
        );

        foreach ([
            'may not produce a decision',
            'may not issue or consume live authority',
            'may not admit live execution',
            'may not adopt a live successor or change live binding state',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'may not start a provider effect',
            'may not authorize retry',
            'may not migrate a live command',
            'may not open Iron Gate or Lazaretto',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    public function testLedgerAndMissionFlowRecordTheTerminalPosture(): void
    {
        $ledger = $this->document('docs/deferred-local-test-ledger.md');
        $flow = $this->document('docs/delegate-mission-flow.md');

        self::assertStringContainsString(
            'Provider Binding Successor Live Adoption Batch 6',
            $ledger,
        );
        self::assertStringContainsString(
            'BATCH_7_TERMINAL_AUDIT_PASSED_PROVIDER_BINDING_SUCCESSOR_LIVE_ADOPTION_READINESS_COMPLETE',
            $flow,
        );
        self::assertStringContainsString(
            'PROVIDER_BINDING_SUCCESSOR_LIVE_ADOPTION_CAMPAIGN_COMPLETE_PRE_LIVE_TRANSITION_ONLY',
            $flow,
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
