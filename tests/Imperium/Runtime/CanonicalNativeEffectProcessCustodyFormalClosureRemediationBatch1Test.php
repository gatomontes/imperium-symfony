<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\NativeEffectContinuationCapabilityContract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectExecutionRecoverySeparationContract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectForwardRecoveryClaimContract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectProcessIncarnationContract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityContract;
use PHPUnit\Framework\TestCase;

final class CanonicalNativeEffectProcessCustodyFormalClosureRemediationBatch1Test extends TestCase
{
    public function testProcessIncarnationRequiresActualPidNonceAndIssuerIdentity(): void
    {
        self::assertSame(
            ['runtime_process_id', 'issuer_owned_random_nonce', 'issuer_object_identity'],
            NativeEffectProcessIncarnationContract::REQUIRED_COMPONENTS,
        );
        foreach (['authority_execution_boundary_id', 'container_service_id', 'caller_supplied_process_label', 'pid_without_nonce'] as $label) {
            self::assertContains($label, NativeEffectProcessIncarnationContract::PROHIBITED_IDENTITY_SUBSTITUTES);
        }
        self::assertTrue(NativeEffectProcessIncarnationContract::REQUIRED_INVARIANTS['current_pid_must_equal_initial_pid']);
        self::assertTrue(NativeEffectProcessIncarnationContract::REQUIRED_INVARIANTS['pid_reuse_requires_fresh_nonce']);
        self::assertFalse(NativeEffectProcessIncarnationContract::REQUIRED_INVARIANTS['fork_inheritance_permitted']);
    }

    public function testContinuationContractForbidsEveryTransferMechanism(): void
    {
        foreach (['runtime_process_id', 'process_incarnation_binding'] as $field) {
            self::assertContains($field, NativeEffectContinuationCapabilityContract::REQUIRED_FIELDS);
        }
        foreach (['serialization_permitted', 'unserialization_permitted', 'clone_permitted', 'fork_inheritance_permitted'] as $rule) {
            self::assertFalse(NativeEffectContinuationCapabilityContract::REQUIRED_INVARIANTS[$rule], $rule);
        }
        self::assertFalse(NativeEffectContinuationCapabilityContract::REQUIRED_INVARIANTS['authority_process_boundary_is_identity']);
    }

    public function testReconciliationAuthorityAndClaimAreExactNoProviderActs(): void
    {
        self::assertSame('FORWARD_COMPLETE_ONLY', NativeEffectReconciliationAuthorityContract::ACT);
        self::assertSame(NativeEffectReconciliationAuthorityContract::ACT, NativeEffectForwardRecoveryClaimContract::ACT);
        foreach (NativeEffectReconciliationAuthorityContract::REQUIRED_FALSE_FLAGS as $flag) {
            self::assertContains($flag, NativeEffectReconciliationAuthorityContract::REQUIRED_FIELDS);
            self::assertFalse(NativeEffectForwardRecoveryClaimContract::REQUIRED_INVARIANTS[$flag]);
        }
        foreach (['effect_admission', 'callback_start', 'sealed_response', 'deterministic_receipt_id'] as $field) {
            self::assertContains($field, NativeEffectReconciliationAuthorityContract::REQUIRED_FIELDS);
            self::assertContains($field, NativeEffectForwardRecoveryClaimContract::REQUIRED_FIELDS);
        }
    }

    public function testThreeActsHaveDisjointInputsAndAcyclicRecoveryLockOrder(): void
    {
        self::assertCount(3, NativeEffectExecutionRecoverySeparationContract::ACTS);
        self::assertSame(['receipt_id'], NativeEffectExecutionRecoverySeparationContract::RECONSTRUCTION_INPUTS);
        self::assertSame(['forward_recovery_claim_id', 'current_time'], NativeEffectExecutionRecoverySeparationContract::FORWARD_RECOVERY_INPUTS);
        self::assertSame([
            'admission_continuation_scope',
            'exact_reconciliation_claim_scope',
            'receipt_immutable_store_scope',
        ], NativeEffectExecutionRecoverySeparationContract::RECOVERY_LOCK_ORDER);
        self::assertFalse(NativeEffectExecutionRecoverySeparationContract::REQUIRED_INVARIANTS['execute_returns_existing_receipt']);
        self::assertFalse(NativeEffectExecutionRecoverySeparationContract::REQUIRED_INVARIANTS['execute_binds_preexisting_response']);
        self::assertFalse(NativeEffectExecutionRecoverySeparationContract::REQUIRED_INVARIANTS['forward_recovery_accepts_callback']);
    }

    public function testContractsHaveNoRuntimePersistenceProviderOrCredentialMechanism(): void
    {
        $root = $this->root().'/src/Imperium/Runtime/ProviderTransition/';
        $source = '';
        foreach ([
            'NativeEffectProcessIncarnationContract.php',
            'NativeEffectReconciliationAuthorityContract.php',
            'NativeEffectForwardRecoveryClaimContract.php',
            'NativeEffectExecutionRecoverySeparationContract.php',
        ] as $file) {
            $source .= file_get_contents($root.$file);
        }
        foreach (['AtomicTransition', 'ImmutableRecordStore', 'random_bytes(', 'getmypid(', 'file_put_contents', 'CredentialBroker', 'AgentMail', 'HttpClient', 'curl_'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source, $forbidden);
        }
    }

    public function testBatchDocumentationPinsRuntimeAndBatchSevenStops(): void
    {
        $docs = $this->read('docs/canonical-native-effect-process-custody-formal-closure-remediation-batch-1-contracts-v1.md')
            .$this->read('docs/handoffs/canonical-native-effect-process-custody-formal-closure-remediation-batch-1-complete.md');
        foreach ([
            'NO_RUNTIME_WIRING', 'BATCH_2_PROCESS_BOUND_CUSTODY_NEXT',
            'BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED',
            'No authority/capability/claim was issued or consumed',
        ] as $marker) {
            self::assertStringContainsStringIgnoringCase($marker, $docs, $marker);
        }
    }

    private function read(string $path): string
    {
        return str_replace(["\r\n", "\r"], "\n", (string) file_get_contents($this->root().'/'.$path));
    }

    private function root(): string
    {
        return dirname(__DIR__, 3);
    }
}
