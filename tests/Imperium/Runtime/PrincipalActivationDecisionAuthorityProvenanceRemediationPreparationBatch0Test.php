<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\ImperatorRuntimePrincipalVersionContract;
use App\Imperium\Runtime\Imperator\ProviderExecutorPrincipalActivationDecisionContract;
use PHPUnit\Framework\TestCase;

final class PrincipalActivationDecisionAuthorityProvenanceRemediationPreparationBatch0Test extends TestCase
{
    public function testPreparationFindsOperatorRootSuccessorRequirement(): void
    {
        $inventory = $this->document(
            'docs/principal-activation-decision-authority-provenance-remediation-preparation-inventory.md',
        );

        foreach ([
            'PREPARATION_BATCH_0_COMPLETE_OPERATOR_ROOT_SCOPE_SUCCESSOR_REQUIRED',
            'Operator Root is the only competent owner',
            'current active Imperator generation cannot self-widen',
            'successor generation is required',
            'Provider-binding authority is not provider-executor-principal activation-decision authority',
        ] as $finding) {
            self::assertNotFalse(stripos($inventory, $finding), $finding);
        }
    }

    public function testInventoryClassifiesEveryRequiredBoundary(): void
    {
        $inventory = $this->document(
            'docs/principal-activation-decision-authority-provenance-remediation-preparation-inventory.md',
        );

        foreach ([
            'EXISTS_CANONICALLY',
            'EXISTS_FRAGMENTED',
            'ABSENT',
            'DEFERRED_BOUNDARY',
            'Exact decision scope field',
            'Caller-authority issuer',
            'Decision producer',
            'Immutable production decision custody',
            'Source-authority resolution',
            'Atomic activation mechanism',
        ] as $finding) {
            self::assertNotFalse(stripos($inventory, $finding), $finding);
        }
    }

    public function testCurrentContractsConfirmTheExactScopeAndProducerGaps(): void
    {
        self::assertNotContains(
            'provider_executor_principal_activation_decision_authority',
            ImperatorRuntimePrincipalVersionContract::REQUIRED_AUTHORITY_SCOPE_FIELDS,
        );
        self::assertFalse(
            ImperatorRuntimePrincipalVersionContract::NON_AUTHORITIES['self_widens_scope'],
        );
        self::assertSame(
            'future-imperator-provider-executor-principal-activation-decision',
            ProviderExecutorPrincipalActivationDecisionContract::PRODUCER_POSTURE,
        );

        $issuer = (string) file_get_contents(
            dirname(__DIR__, 3).'/src/Imperium/Runtime/LaCortine/'
                .'DeterministicTransitionCallerAuthorityIssuanceService.php',
        );
        self::assertStringNotContainsString(
            'DECIDE_EXACT_PROVIDER_EXECUTOR_PRINCIPAL_ACTIVATION',
            $issuer,
        );
        self::assertStringNotContainsString(
            'ISSUE_EXACT_PROVIDER_EXECUTOR_PRINCIPAL_ACTIVATION_AUTHORITY',
            $issuer,
        );
    }

    public function testPreparationDefinesOrderedAtomicRecoveryAndSecretPosture(): void
    {
        $inventory = $this->document(
            'docs/principal-activation-decision-authority-provenance-remediation-preparation-inventory.md',
        );

        foreach ([
            'Competent authority chain',
            'Scope grant',
            'Successor commit',
            'Successor activation',
            'Caller authority',
            'Decision',
            'Before any combined commit',
            'exact replay must converge',
            'UNKNOWN_REPLAY_PROHIBITED',
            'credential bytes',
            'serialized process-local capabilities',
        ] as $finding) {
            self::assertNotFalse(stripos($inventory, $finding), $finding);
        }
    }

    public function testHandoffAuthorizesAuthorityEmptyBatchOneOnly(): void
    {
        $handoff = $this->document(
            'docs/handoffs/principal-activation-decision-authority-provenance-remediation-preparation-batch-0-complete.md',
        );

        foreach ([
            'Only Batch 1 may next be considered',
            'authority-empty contracts',
            'Operator Root narrow-scope grant',
            'PENDING_ACTIVATION',
            'decision-issuance authorization',
            'Contract existence creates no scope',
            'Iron Gate and Lazaretto remained closed',
            'approximately seven batches',
        ] as $gate) {
            self::assertNotFalse(stripos($handoff, $gate), $gate);
        }
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
