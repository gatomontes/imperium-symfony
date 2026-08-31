<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

final class ProviderBindingActivationStateReconciliationBatch6TerminalAuditTest
    extends ProviderBindingActivationStateReconciliationBatch5AdversarialAuditTest
{
    public function testEveryCampaignResultIsRepresentedExactly(): void
    {
        $results = [
            'docs/handoffs/provider-binding-activation-state-reconciliation-preparation-batch-0-complete.md'
                => 'PREPARATION_BATCH_0_COMPLETE_IMMUTABLE_BINDING_SUCCESSOR_REQUIRED',
            'docs/handoffs/provider-binding-activation-state-reconciliation-batch-1-complete.md'
                => 'BATCH_1_AUTHORITY_EMPTY_IMMUTABLE_BINDING_SUCCESSOR_CONTRACTS_COMPLETE',
            'docs/handoffs/provider-binding-activation-state-reconciliation-batch-2-complete.md'
                => 'BATCH_2_FAIL_CLOSED_VALIDATORS_AND_IMMUTABLE_FIXTURE_STORES_COMPLETE',
            'docs/handoffs/provider-binding-activation-state-reconciliation-batch-3-complete.md'
                => 'BATCH_3_OFFLINE_INTERRUPTION_REPLAY_AND_CONTENTION_PROOF_COMPLETE',
            'docs/handoffs/provider-binding-activation-state-reconciliation-batch-4-complete.md'
                => 'BATCH_4_READ_ONLY_AGGREGATE_RECONSTRUCTION_COMPLETE',
            'docs/handoffs/provider-binding-activation-state-reconciliation-batch-5-complete.md'
                => 'BATCH_5_ADVERSARIAL_READINESS_AUDIT_PASSED',
        ];

        foreach ($results as $path => $result) {
            self::assertNotFalse(stripos($this->document($path), $result), $result);
        }
    }

    public function testTerminalLedgerRetainsEveryClearAndTheRootKeyRepair(): void
    {
        $ledger = $this->document('docs/deferred-local-test-ledger.md');

        foreach ([
            'Provider Binding Activation State Reconciliation Preparation Batch 0',
            'Provider Binding Activation State Reconciliation Batch 1',
            'Provider Binding Activation State Reconciliation Batch 2',
            'Provider Binding Activation State Reconciliation Batch 3',
            'Provider Binding Activation State Reconciliation Batch 4',
            'Provider Binding Activation State Reconciliation Batch 5',
            'Verification repair PR: #648',
            'CLEAR_OPERATOR_REPORTED_AFTER_ROOT_KEY_REPAIR',
        ] as $entry) {
            self::assertNotFalse(stripos($ledger, $entry), $entry);
        }
    }

    public function testTerminalClosurePreservesThePreProviderPerimeter(): void
    {
        $audit = $this->document(
            'docs/provider-binding-activation-state-reconciliation-batch-6-terminal-audit.md',
        );
        $closure = $this->document(
            'docs/handoffs/provider-binding-activation-state-reconciliation-campaign-complete.md',
        );

        foreach ([
            'BATCH_6_TERMINAL_AUDIT_PASSED_PRE_PROVIDER_RECONCILIATION_COMPLETE',
            'immutable operation-scoped lifecycle successor',
            'exact ACTIVE principal activation',
            'original provider binding remains BOUND_INACTIVE',
            'legacy ACTIVATED_UNCONSUMED evidence was not promoted',
            'same-root contention',
            'no credential or capability was handled',
            'no provider was invoked',
            'no external I/O was performed',
            'Iron Gate and Lazaretto remain closed',
            'UNKNOWN_REPLAY_PROHIBITED remains binding',
        ] as $finding) {
            self::assertNotFalse(stripos($audit, $finding), $finding);
        }

        foreach ([
            'PROVIDER_BINDING_ACTIVATION_STATE_RECONCILIATION_CAMPAIGN_COMPLETE_PRE_PROVIDER_ONLY',
            'No reconciliation batch remains',
            'does not authorize production adoption',
            'may not activate a provider binding',
            'may not issue or consume authority',
            'may not handle a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'separate explicitly selected campaign',
        ] as $boundary) {
            self::assertNotFalse(stripos($closure, $boundary), $boundary);
        }
    }

    public function testTerminalAuditAddsNoProductionOrEffectDependency(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3).'/src/Imperium/Runtime/Imperator/'
                .'ProviderBindingActivationStateReconciliationAdversarialAuditService.php',
        );

        foreach ([
            'ImmutableRecordStore',
            'AtomicTransition',
            'AuthorityConsumptionStore',
            'CredentialBroker',
            'AgentMailEmailTransport',
            'ProviderBindingActivationService',
            'GovernedProviderExecutionCombinedAdmissionService',
            'public function produce',
            'public function issue',
            'public function consume',
            'public function activate',
            'IronGate',
            'Lazaretto',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }
}
