<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorProductionAdoptionBatch6TerminalAuditTest extends TestCase
{
    public function testTerminalAuditRetainsTheExactOfflineProofChain(): void
    {
        $document = $this->document('docs/provider-binding-successor-production-adoption-batch-6-terminal-audit.md');

        foreach ([
            'BATCH_6_TERMINAL_AUDIT_PASSED_OFFLINE_PRODUCTION_ADOPTION_READINESS_COMPLETE',
            'BATCH_2_REFUSED_CYCLIC_DECISION_AUTHORITY_DIGEST_DEPENDENCY',
            'BATCH_1A_AUTHORITY_EMPTY_ACYCLIC_DECISION_AUTHORITY_CONTRACTS_COMPLETE',
            'BATCH_2A_FAIL_CLOSED_V2_VALIDATORS_AND_IMMUTABLE_OFFLINE_FIXTURE_STORES_COMPLETE',
            'BATCH_3_OFFLINE_INTERRUPTION_REPLAY_AND_CONTENTION_PROOF_COMPLETE',
            'BATCH_4_READ_ONLY_AGGREGATE_RECONSTRUCTION_COMPLETE',
            'BATCH_5_ADVERSARIAL_READINESS_AUDIT_PASSED',
            'The defective v1 contracts remain historical refusal evidence.',
            'The corrected v2 lineage remains offline evidence.',
        ] as $finding) {
            self::assertStringContainsString($finding, $document);
        }
    }

    public function testCampaignClosurePreservesTheClosedRuntimePerimeter(): void
    {
        $document = $this->document('docs/handoffs/provider-binding-successor-production-adoption-campaign-complete.md');

        foreach ([
            'PROVIDER_BINDING_SUCCESSOR_PRODUCTION_ADOPTION_CAMPAIGN_COMPLETE_PRE_PRODUCTION_ONLY',
            'The provider binding remains BOUND_INACTIVE.',
            'The required v3 execution admission remains NOT_IMPLEMENTED.',
            'No production-adoption batch remains.',
            'may not activate a principal or provider binding',
            'may not issue or consume authority',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'may not migrate a live command',
            'may not open Iron Gate or Lazaretto',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $document);
        }

        self::assertStringNotContainsString('BOUND_ACTIVE', $document);
    }

    public function testProductionWorkRequiresASeparatelySelectedCampaign(): void
    {
        $required = 'A separate explicitly selected campaign is required before any production decision issuance, authority issuance or consumption, successor creation, v3 execution admission or live adoption may be considered.';

        self::assertStringContainsString(
            $required,
            $this->document('docs/provider-binding-successor-production-adoption-batch-6-terminal-audit.md')
        );
        self::assertStringContainsString(
            $required,
            $this->document('docs/handoffs/provider-binding-successor-production-adoption-campaign-complete.md')
        );
    }

    public function testLedgerAndMissionFlowRecordTheTerminalPosture(): void
    {
        $ledger = $this->document('docs/deferred-local-test-ledger.md');
        $flow = $this->document('docs/delegate-mission-flow.md');

        self::assertStringContainsString('Provider Binding Successor Production Adoption Batch 5 adversarial audit', $ledger);
        self::assertStringContainsString('BATCH_6_TERMINAL_AUDIT_PASSED_OFFLINE_PRODUCTION_ADOPTION_READINESS_COMPLETE', $flow);
        self::assertStringContainsString('PROVIDER_BINDING_SUCCESSOR_PRODUCTION_ADOPTION_CAMPAIGN_COMPLETE_PRE_PRODUCTION_ONLY', $flow);
    }

    private function document(string $path): string
    {
        $contents = file_get_contents(dirname(__DIR__, 3).'/'.$path);
        self::assertNotFalse($contents);

        return $contents;
    }
}
