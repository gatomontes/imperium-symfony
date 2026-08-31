<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorProductionRealizationBatch7TerminalAuditTest extends TestCase
{
    public function testTerminalAuditRetainsTheCompleteProofChain(): void
    {
        $terminal = $this->document(
            'docs/provider-binding-successor-production-realization-batch-7-terminal-audit.md',
        );

        foreach ([
            'PREPARATION_BATCH_0_COMPLETE_PRODUCTION_REALIZATION_BOUNDARIES_CLASSIFIED',
            'BATCH_1_AUTHORITY_EMPTY_PRODUCTION_DECISION_PRINCIPAL_AND_ISSUER_CONTRACTS_COMPLETE',
            'BATCH_2_AUTHORITY_EMPTY_SUCCESSOR_CREATION_ISSUANCE_AND_DURABLE_CUSTODY_CONTRACTS_COMPLETE',
            'BATCH_3_INERT_SAME_ROOT_ATOMIC_CONSUMPTION_AND_SUCCESSOR_CREATION_BOUNDARY_COMPLETE',
            'BATCH_4_AUTHORITY_EMPTY_SUCCESSOR_ADMISSION_V3_CONTRACT_AND_VALIDATOR_COMPLETE',
            'BATCH_5_AUTHORITY_EMPTY_ADOPTION_DECISION_AND_SUCCESSOR_TO_V3_JOIN_CONTRACTS_COMPLETE',
            'BATCH_6_READ_ONLY_INTERRUPTION_REPLAY_CONTENTION_AND_ADVERSARIAL_PROOF_PASSED',
        ] as $result) {
            self::assertStringContainsString($result, $terminal);
        }
    }

    public function testTerminalDispositionClosesExactlyThisCampaign(): void
    {
        $terminal = $this->document(
            'docs/provider-binding-successor-production-realization-batch-7-terminal-audit.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-production-realization-campaign-complete.md',
        );

        foreach ([
            'BATCH_7_TERMINAL_AUDIT_PASSED_PROVIDER_BINDING_SUCCESSOR_PRODUCTION_REALIZATION_COMPLETE',
            'PROVIDER_BINDING_SUCCESSOR_PRODUCTION_REALIZATION_CAMPAIGN_COMPLETE_PRE_PROVIDER_EFFECT_ONLY',
            'No production-realization batch remains.',
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

    public function testClosureRequiresSeparateSelectionBeforeAnyProviderEffect(): void
    {
        $required = 'A separate explicitly selected campaign is required before live adoption, execution admission, provider-binding activation, credential or capability handling, provider invocation, external I/O or any provider effect may be considered.';

        self::assertStringContainsString(
            $required,
            $this->document(
                'docs/provider-binding-successor-production-realization-batch-7-terminal-audit.md',
            ),
        );
        self::assertStringContainsString(
            $required,
            $this->document(
                'docs/handoffs/provider-binding-successor-production-realization-campaign-complete.md',
            ),
        );
    }

    public function testClosurePreservesEveryNonAuthority(): void
    {
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-production-realization-campaign-complete.md',
        );

        foreach ([
            'may not decide or perform live adoption',
            'may not admit execution',
            'may not issue or consume live authority',
            'may not create a live successor',
            'may not activate a principal or provider binding',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'may not start a provider effect',
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
            'Provider Binding Successor Production Realization Batch 6',
            $ledger,
        );
        self::assertStringContainsString(
            'BATCH_7_TERMINAL_AUDIT_PASSED_PROVIDER_BINDING_SUCCESSOR_PRODUCTION_REALIZATION_COMPLETE',
            $flow,
        );
        self::assertStringContainsString(
            'PROVIDER_BINDING_SUCCESSOR_PRODUCTION_REALIZATION_CAMPAIGN_COMPLETE_PRE_PROVIDER_EFFECT_ONLY',
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
