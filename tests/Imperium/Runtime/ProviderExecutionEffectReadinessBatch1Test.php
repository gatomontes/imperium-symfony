<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\AgentMailDirectSendAssuranceProfileContract;
use App\Imperium\Runtime\LaCortine\ProviderAssuranceEvidenceAdmissionContract;
use App\Imperium\Runtime\LaCortine\ProviderAssuranceEvidenceSourceContract;
use PHPUnit\Framework\TestCase;

final class ProviderExecutionEffectReadinessBatch1Test extends TestCase
{
    public function testAssuranceContractsAreSeparatelyVersionedAndAuthorityEmpty(): void
    {
        $contracts = [
            ProviderAssuranceEvidenceSourceContract::class,
            AgentMailDirectSendAssuranceProfileContract::class,
            ProviderAssuranceEvidenceAdmissionContract::class,
        ];

        self::assertCount(3, array_unique(array_map(
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

    public function testAgentMailProfileBindsExactOperationAndExplicitUnknowns(): void
    {
        self::assertSame('agentmail', AgentMailDirectSendAssuranceProfileContract::PROVIDER_ID);
        self::assertSame('email.send', AgentMailDirectSendAssuranceProfileContract::OPERATION);
        self::assertSame(
            'POST /v0/inboxes/{inbox_id}/messages/send',
            AgentMailDirectSendAssuranceProfileContract::ENDPOINT_TEMPLATE,
        );
        self::assertSame([
            'organization',
            'endpoint',
            'inbox_id',
            'message_content',
        ], AgentMailDirectSendAssuranceProfileContract::REQUIRED_REQUEST_EQUIVALENCE_FIELDS);
        self::assertSame([
            'in_progress_duplicate_semantics',
            'query_by_idempotency_key',
            'remote_cryptographic_authorship',
            'completion_time_without_response',
        ], AgentMailDirectSendAssuranceProfileContract::REQUIRED_UNKNOWN_FIELDS);
        self::assertSame(
            'UNKNOWN_REPLAY_PROHIBITED',
            AgentMailDirectSendAssuranceProfileContract::REPLAY_POSTURE,
        );
    }

    public function testAdmissionContractPreservesUnknownOutcomeAndThreatLimits(): void
    {
        self::assertContains(
            'EVIDENCE_ADMITTED_NO_EXECUTION_AUTHORITY',
            ProviderAssuranceEvidenceAdmissionContract::STATUSES,
        );
        self::assertSame(
            'UNKNOWN_REPLAY_PROHIBITED',
            ProviderAssuranceEvidenceAdmissionContract::UNKNOWN_OUTCOME_POSTURE,
        );
        self::assertSame([
            'integrity_posture',
            'deployment_posture',
            'authenticated_channel_trust_only',
            'hostile_writer_non_forgeability_claimed',
            'distributed_execution_claimed',
        ], ProviderAssuranceEvidenceAdmissionContract::REQUIRED_THREAT_MODEL_FIELDS);
    }

    public function testBatchDocumentationPreservesClosedRuntimePerimeter(): void
    {
        $repository = dirname(__DIR__, 3);
        $documentation = (string) preg_replace('/\\s+/', ' ', (
            (string) file_get_contents(
                $repository.'/docs/provider-execution-effect-readiness-assurance-contracts.md',
            )
        ).(
            (string) file_get_contents(
                $repository.'/docs/handoffs/provider-execution-effect-readiness-batch-1-complete.md',
            )
        ));

        foreach ([
            'BATCH_1_AUTHORITY_EMPTY_PROVIDER_ASSURANCE_CONTRACTS_COMPLETE',
            'No producer, validator, fixture, immutable evidence record or runtime consumer exists',
            'Only Batch 2 may next be considered',
            'changed no runtime behavior',
            'activated no principal or binding',
            'handled no credential',
            'invoked no provider',
            'performed no external I/O',
            'authorized no retry',
            'Iron Gate and Lazaretto closed',
        ] as $required) {
            self::assertStringContainsString($required, $documentation);
        }
    }
}
