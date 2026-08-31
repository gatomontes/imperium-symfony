<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

final class ProviderEffectPrincipalBindingActivationResumptionBatch6TerminalAuditTest
    extends ProviderEffectPrincipalBindingActivationResumptionBatch5Test
{
    public function testEveryResumptionResultIsRepresentedExactly(): void
    {
        $results = [
            'docs/handoffs/provider-effect-principal-binding-activation-resumption-batch-1-complete.md'
                => 'RESUMPTION_BATCH_1_AUTHORITY_EMPTY_CANONICAL_RESOLUTION_AND_ACTIVATION_INPUT_CONTRACTS_COMPLETE',
            'docs/handoffs/provider-effect-principal-binding-activation-resumption-batch-2-complete.md'
                => 'RESUMPTION_BATCH_2_PURE_VALIDATORS_AND_SEGREGATED_IMMUTABLE_FIXTURE_STORES_COMPLETE',
            'docs/handoffs/provider-effect-principal-binding-activation-resumption-batch-3-complete.md'
                => 'RESUMPTION_BATCH_3_READ_ONLY_AGGREGATE_RECONSTRUCTION_PROOF_COMPLETE',
            'docs/handoffs/provider-effect-principal-binding-activation-resumption-batch-4-complete.md'
                => 'RESUMPTION_BATCH_4_CANONICAL_ATOMIC_PRINCIPAL_ACTIVATION_ENTRY_POINT_COMPLETE',
            'docs/handoffs/provider-effect-principal-binding-activation-resumption-batch-5-complete.md'
                => 'RESUMPTION_BATCH_5_ADVERSARIAL_AUDIT_COMPLETE',
        ];

        foreach ($results as $path => $result) {
            self::assertNotFalse(stripos($this->document($path), $result), $result);
        }
    }

    public function testTerminalAuditRetainsEveryClearAndRepair(): void
    {
        $ledger = $this->document('docs/deferred-local-test-ledger.md');

        foreach ([
            'Provider Effect Principal and Binding Activation Resumption Batch 1',
            'Provider Effect Principal and Binding Activation Resumption Batch 2',
            'Provider Effect Principal and Binding Activation Resumption Batch 3',
            'Provider Effect Principal and Binding Activation Resumption Batch 4',
            'Provider Effect Principal and Binding Activation Resumption Batch 5',
            'Repair PR: #633',
            'Repair PR: #635',
            'Repair PR: #637',
            'Repair PRs: #639 and #640',
            'CLEAR_OPERATOR_REPORTED_AFTER_DOCUMENTATION_REPAIRS',
        ] as $entry) {
            self::assertNotFalse(stripos($ledger, $entry), $entry);
        }
    }

    public function testTerminalClosurePreservesClosedRuntimePerimeter(): void
    {
        $audit = $this->document(
            'docs/provider-effect-principal-binding-activation-resumption-batch-6-terminal-audit.md',
        );
        $closure = $this->document(
            'docs/handoffs/provider-effect-principal-binding-activation-resumption-campaign-complete.md',
        );

        foreach ([
            'RESUMPTION_BATCH_6_TERMINAL_AUDIT_PASSED',
            'single combined principal-activation winner',
            'no consumption-only state',
            'provider binding remains BOUND_INACTIVE',
            'no credential or capability was handled',
            'no provider was invoked',
            'no external I/O was performed',
            'Iron Gate and Lazaretto remain closed',
            'UNKNOWN_REPLAY_PROHIBITED remains binding',
        ] as $finding) {
            self::assertNotFalse(stripos($audit, $finding), $finding);
        }

        foreach ([
            'PROVIDER_EFFECT_PRINCIPAL_BINDING_ACTIVATION_RESUMPTION_CAMPAIGN_COMPLETE',
            'No resumption batch remains',
            'does not authorize live provider adoption',
            'may not activate a provider binding',
            'may not handle a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'separate explicitly selected campaign',
        ] as $boundary) {
            self::assertNotFalse(stripos($closure, $boundary), $boundary);
        }
    }

    public function testTerminalAuditAddsNoProductionRuntimePath(): void
    {
        $service = (string) file_get_contents(
            dirname(__DIR__, 3)
                .'/src/Imperium/Runtime/LaCortine/'
                .'ProviderExecutorPrincipalCanonicalActivationService.php',
        );

        foreach ([
            'ProviderBindingActivationService',
            'CredentialCapability',
            'EnvironmentCredentialBroker',
            'AgentMailEmailTransport',
            'GovernedProviderExecutionCombinedAdmissionService',
            'DurableProviderExecutionAuthority',
            'IronGate',
            'Lazaretto',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $service);
        }
    }
}
