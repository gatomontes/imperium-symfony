<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\DurableProviderExecutionAuthorityContract;
use App\Imperium\Runtime\LaCortine\ProviderExecutionBoundaryContract;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalContract;
use App\Imperium\Runtime\LaCortine\SingleOperationProviderBindingActivationContract;
use PHPUnit\Framework\TestCase;

final class ProviderExecutionBoundaryRedesignBatch1Test extends TestCase
{
    public function testRedesignedContractsAreSeparatelyVersionedAndAuthorityEmpty(): void
    {
        $contracts = [
            ProviderExecutionBoundaryContract::class,
            ProviderExecutorPrincipalContract::class,
            DurableProviderExecutionAuthorityContract::class,
            SingleOperationProviderBindingActivationContract::class,
        ];

        self::assertCount(4, array_unique(array_map(
            static fn (string $contract): string => $contract::SCHEMA,
            $contracts,
        )));

        foreach ($contracts as $contract) {
            self::assertSame(1, $contract::VERSION);
            self::assertNotSame('', $contract::PRODUCER_POSTURE);
            self::assertNotEmpty($contract::CONSUMER_POSTURES);
            self::assertContains('record_digest', $contract::REQUIRED_FIELDS);
            foreach ($contract::NON_AUTHORITIES as $permission) {
                self::assertFalse($permission);
            }
        }
    }

    public function testBoundaryFixesSameProcessOrderingAndDeclaredThreatModel(): void
    {
        self::assertSame('SAME_PROCESS_GOVERNED_EXECUTOR', ProviderExecutionBoundaryContract::CANDIDATE_POSTURE);
        self::assertSame([
            'authority_consumed_pre_resolution',
            'effect_start_committed_pre_resolution',
            'effect_start_committed_pre_io',
            'credential_resolution_inside_winning_boundary',
        ], ProviderExecutionBoundaryContract::REQUIRED_ADMISSION_ORDERING_FIELDS);
        self::assertSame([
            'integrity_posture',
            'deployment_posture',
            'hostile_writer_non_forgeability_claimed',
            'multi_host_consensus_claimed',
            'split_brain_resistance_claimed',
        ], ProviderExecutionBoundaryContract::REQUIRED_THREAT_MODEL_FIELDS);

        foreach (ProviderExecutionBoundaryContract::SECRET_EXCLUSION as $permission) {
            self::assertFalse($permission);
        }
    }

    public function testDurableAuthorityBindsExactExecutorRequestProviderAndValidity(): void
    {
        foreach ([
            'execution_boundary',
            'executor_principal',
            'tool_authority',
            'effect_authorization',
            'provider_binding_activation',
            'provider_binding',
            'request',
            'destination_policy',
            'assurance_profile',
            'validity',
            'authority_single_use',
            'consumed',
            'continuing_authority',
        ] as $field) {
            self::assertContains($field, DurableProviderExecutionAuthorityContract::REQUIRED_FIELDS);
        }

        self::assertSame([
            'request_id',
            'commission_id',
            'operation',
            'exact_destination',
            'payload_digest',
            'request_fingerprint',
        ], DurableProviderExecutionAuthorityContract::REQUIRED_REQUEST_FIELDS);
    }

    public function testActivationIsSingleOperationAndCannotIssueExecutionAuthority(): void
    {
        self::assertContains('single_operation', SingleOperationProviderBindingActivationContract::REQUIRED_FIELDS);
        self::assertSame([
            'ACTIVATED_UNCONSUMED',
            'CONSUMED_PRE_RESOLUTION_PRE_IO',
            'EXPIRED',
            'REVOKED',
        ], SingleOperationProviderBindingActivationContract::STATUSES);
        self::assertFalse(SingleOperationProviderBindingActivationContract::NON_AUTHORITIES['issues_execution_authority']);
        self::assertFalse(SingleOperationProviderBindingActivationContract::NON_AUTHORITIES['resolves_credentials']);
        self::assertFalse(SingleOperationProviderBindingActivationContract::NON_AUTHORITIES['starts_external_io']);
    }

    public function testBatchDocumentationPreservesTheClosedRuntimePerimeter(): void
    {
        $root = dirname(__DIR__, 3);
        $contracts = (string) file_get_contents($root.'/docs/provider-execution-boundary-redesign-contracts.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/provider-execution-boundary-redesign-batch-1-complete.md');

        foreach ([
            '`BATCH_1_CONTRACTS_COMPLETE_NO_IMPLEMENTATION`',
            'Contract existence grants no authority',
            'No producer, issuer, attestor, activation transition, consumer',
            'CredentialCapability',
            'UNKNOWN_REPLAY_PROHIBITED',
        ] as $proof) {
            self::assertStringContainsString($proof, $contracts);
        }

        foreach ([
            'Only Batch 2 may next be considered',
            'may define contracts and validators only',
            'may not install or activate a principal',
            'handle a credential or capability',
            'external I/O',
            'Iron Gate',
            'Lazaretto',
            'Provider Execution Assurance remains paused',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }
}
