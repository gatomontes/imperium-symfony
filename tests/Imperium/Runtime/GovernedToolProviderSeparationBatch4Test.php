<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\AgentMailProviderEvidenceDecoder;
use App\Imperium\Runtime\LaCortine\AgentMailProviderProfile;
use App\Imperium\Runtime\LaCortine\AgentMailProviderRequestEncoder;
use App\Imperium\Runtime\LaCortine\ProviderEvidenceDecoderContract;
use App\Imperium\Runtime\LaCortine\ProviderRequestEncoderContract;
use PHPUnit\Framework\TestCase;

final class GovernedToolProviderSeparationBatch4Test extends TestCase
{
    public function testProfileExtractsExactAgentMailFactsWithoutAuthorityOrIo(): void
    {
        self::assertSame('agentmail', AgentMailProviderProfile::PROVIDER_ID);
        self::assertSame('env:AGENTMAIL_API_KEY', AgentMailProviderProfile::CREDENTIAL_REFERENCE_SYNTAX);
        self::assertSame('Bearer', AgentMailProviderProfile::AUTHORIZATION_SCHEME);
        self::assertSame(['message_id', 'thread_id'], AgentMailProviderProfile::RECEIPT_FIELDS);
        foreach (AgentMailProviderProfile::BOUNDARY as $permission) {
            self::assertFalse($permission);
        }
    }

    public function testEncoderBuildsTransientRequestAndSecretFreeEvidence(): void
    {
        $payload = '{"to":["recipient@example.test"],"subject":"Test","text":"Body"}';
        $encoded = (new AgentMailProviderRequestEncoder())->encode($this->binding(), 'https://api.agentmail.to/v0/inboxes/inbox-test/messages/send', $payload, 'opaque-secret', 'exact-key');

        self::assertSame('Bearer opaque-secret', $encoded->request()['headers']['Authorization']);
        self::assertSame($payload, $encoded->request()['body']);
        self::assertSame(ProviderRequestEncoderContract::REQUIRED_OUTPUT_FIELDS, array_keys($encoded->evidence()));
        self::assertFalse($encoded->evidence()['secret_persistence_permitted']);
        self::assertStringNotContainsString('opaque-secret', json_encode($encoded->evidence(), JSON_THROW_ON_ERROR));
    }

    public function testEncoderRefusesProviderAndDestinationSubstitution(): void
    {
        $binding = $this->binding();
        $binding['provider_implementation']['provider_id'] = 'substitute';
        try {
            (new AgentMailProviderRequestEncoder())->encode($binding, 'https://api.agentmail.to/v0/inboxes/inbox-test/messages/send', '{"to":["a@example.test"]}', 'opaque', 'key');
            self::fail('Provider substitution was accepted.');
        } catch (\RuntimeException $exception) {
            self::assertSame('GTP400_AGENTMAIL_ENCODER_BINDING_INVALID', $exception->getMessage());
        }

        $this->expectExceptionMessage('GTP401_AGENTMAIL_ENCODER_DESTINATION_REJECTED');
        (new AgentMailProviderRequestEncoder())->encode($this->binding(), 'https://example.test/send', '{"to":["a@example.test"]}', 'opaque', 'key');
    }

    public function testDecoderProducesSealedProviderSpecificAttributesWithoutAdmission(): void
    {
        $content = '{"message_id":"msg-1","thread_id":"thread-1"}';
        $decoded = (new AgentMailProviderEvidenceDecoder())->decode($this->binding(), [
            'id' => 'raw-provider-result-1',
            'digest' => str_repeat('8', 64),
            'schema' => 'imperium.la-cortine.raw-provider-result/v1',
            'content_digest' => hash('sha256', $content),
        ], $content, new \DateTimeImmutable('2026-08-29T01:30:00+00:00'));

        self::assertSame(ProviderEvidenceDecoderContract::REQUIRED_OUTPUT_FIELDS, array_keys($decoded));
        self::assertSame(['provider_message_id' => 'msg-1', 'provider_thread_id' => 'thread-1'], $decoded['normalized_attributes']);
        self::assertTrue($decoded['sealed']);
    }

    public function testDecoderRefusesChangedRawBytesAndMissingReceiptIdentity(): void
    {
        $content = '{"message_id":"msg-1","thread_id":"thread-1"}';
        try {
            (new AgentMailProviderEvidenceDecoder())->decode($this->binding(), ['id' => 'raw-1', 'digest' => str_repeat('8', 64), 'schema' => 'raw/v1', 'content_digest' => hash('sha256', $content)], $content.' ', new \DateTimeImmutable());
            self::fail('Changed raw bytes were accepted.');
        } catch (\RuntimeException $exception) {
            self::assertSame('GTP410_AGENTMAIL_DECODER_CONTEXT_INVALID', $exception->getMessage());
        }

        $this->expectExceptionMessage('GTP411_AGENTMAIL_RECEIPT_INVALID');
        $bad = '{"message_id":"msg-1"}';
        (new AgentMailProviderEvidenceDecoder())->decode($this->binding(), ['id' => 'raw-2', 'digest' => str_repeat('9', 64), 'schema' => 'raw/v1', 'content_digest' => hash('sha256', $bad)], $bad, new \DateTimeImmutable());
    }

    public function testBatchFourLeavesLiveConsumersUntouchedAndDocumentsBatchFiveOnly(): void
    {
        $root = dirname(__DIR__, 3);
        $command = (string) file_get_contents($root.'/src/Command/AgentMailEmailSendCommand.php');
        $transport = (string) file_get_contents($root.'/src/Imperium/Runtime/LaCortine/AgentMailEmailTransport.php');
        self::assertStringNotContainsString('AgentMailProviderRequestEncoder', $command.$transport);
        self::assertStringNotContainsString('AgentMailProviderEvidenceDecoder', $command.$transport);

        $handoff = (string) file_get_contents($root.'/docs/handoffs/governed-tool-provider-separation-batch-4-complete.md');
        foreach (['Only Batch 5 may next be considered', 'Runtime behavior is unchanged', 'No credential was resolved', 'Batch 5 is not authorized'] as $proof) {
            self::assertStringContainsString($proof, $handoff);
        }
    }

    private function binding(): array
    {
        return [
            'schema' => 'imperium.la-cortine.provider-implementation-binding/v1',
            'binding_id' => 'provider-implementation-binding-aaaaaaaaaaaaaaaaaaaa',
            'record_digest' => str_repeat('7', 64),
            'provider_implementation' => ['provider_id' => AgentMailProviderProfile::PROVIDER_ID, 'adapter_id' => AgentMailProviderProfile::ADAPTER_ID, 'adapter_version' => AgentMailProviderProfile::ADAPTER_VERSION],
            'credential_family' => ['family_id' => AgentMailProviderProfile::CREDENTIAL_FAMILY_ID, 'provider_id' => AgentMailProviderProfile::PROVIDER_ID, 'secret_persistence_permitted' => false],
            'request_encoder' => ['id' => AgentMailProviderProfile::REQUEST_ENCODER_ID],
            'evidence_decoder' => ['id' => AgentMailProviderProfile::EVIDENCE_DECODER_ID],
        ];
    }
}
