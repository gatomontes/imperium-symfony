<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\DeterministicProviderResponseEnvelopeContract;
use PHPUnit\Framework\TestCase;

final class IronGateEvidenceAuthenticityRemediationBatch1Test extends TestCase
{
    public function testResponseEnvelopeContractHasExactProducerConsumersAndEvidenceShape(): void
    {
        self::assertSame('imperium.la-cortine.deterministic-provider-response-envelope/v1', DeterministicProviderResponseEnvelopeContract::SCHEMA);
        self::assertSame('la-cortine.journal-bound-provider-invocation', DeterministicProviderResponseEnvelopeContract::PRODUCER);
        self::assertSame([
            'la-cortine.deterministic-raw-provider-result-sealer',
            'la-cortine.deterministic-receipt-reconstructor',
        ], DeterministicProviderResponseEnvelopeContract::CONSUMERS);
        self::assertSame(['id', 'digest'], DeterministicProviderResponseEnvelopeContract::REQUIRED_REFERENCE_FIELDS);
        foreach (['provider_invocation_admission', 'effect_start_journal', 'execution_claim', 'source_authorization', 'provider_observation', 'produced_by', 'record_digest'] as $field) self::assertContains($field, DeterministicProviderResponseEnvelopeContract::REQUIRED_FIELDS);
        foreach (['operation', 'destination', 'payload_digest', 'provider_idempotency_key', 'request_fingerprint'] as $field) self::assertContains($field, DeterministicProviderResponseEnvelopeContract::REQUIRED_REQUEST_FIELDS);
        foreach (['http_status', 'headers_digest', 'content_digest', 'sealed_content_reference', 'callback_started_at', 'response_observed_at', 'received_at'] as $field) self::assertContains($field, DeterministicProviderResponseEnvelopeContract::REQUIRED_OBSERVATION_FIELDS);
    }

    public function testDocumentationForbidsCallerNominatedProviderTruthAndKeepsImplementationClosed(): void
    {
        $root = dirname(__DIR__, 3);
        $contract = (string) file_get_contents($root.'/docs/iron-gate-callback-bound-provider-response-envelope.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/iron-gate-evidence-authenticity-remediation-batch-1-complete.md');

        foreach (['`SOLE_PRODUCER_IMPLEMENTED_NO_CONSUMER_MIGRATED`', 'only permitted producer posture', 'may not supply', '`UNKNOWN_REPLAY_PROHIBITED`', 'No external I/O occurs'] as $limit) self::assertStringContainsString($limit, $contract);
        foreach (['Only Batch 2 may next be considered', 'No producer or consumer was implemented or migrated', 'Batch 2 is not authorized', 'Live AgentMail'] as $limit) self::assertStringContainsString($limit, $handoff);
    }
}
