<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\AgentMailProviderEvidenceDecoder;
use App\Imperium\Runtime\LaCortine\AgentMailProviderProfile;
use App\Imperium\Runtime\LaCortine\NormalizedToolResultAdmissionService;
use App\Imperium\Runtime\LaCortine\NormalizedToolResultContract;
use App\Imperium\Runtime\LaCortine\ProviderBoundEvidenceNormalizationService;
use App\Imperium\Runtime\LaCortine\ProviderNeutralRawEvidenceContract;
use App\Imperium\Runtime\LaCortine\ProviderNeutralRawEvidenceService;
use PHPUnit\Framework\TestCase;

final class GovernedToolProviderSeparationBatch6Test extends TestCase
{
    private string $root;

    protected function setUp(): void { $this->root = sys_get_temp_dir().'/imperium-normalized-evidence-'.bin2hex(random_bytes(5)); mkdir($this->root, 0770, true); }
    protected function tearDown(): void { $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST); foreach ($it as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname()); rmdir($this->root); }

    public function testRawEvidencePreservesBytesWithoutProviderInterpretation(): void
    {
        $raw = $this->raw();
        self::assertSame(ProviderNeutralRawEvidenceContract::REQUIRED_FIELDS, array_keys($raw));
        self::assertSame(hash('sha256', $this->content()), $raw['provider_observation']['content_digest']);
        self::assertStringNotContainsString('message_id', json_encode($raw, JSON_THROW_ON_ERROR));
        foreach (ProviderNeutralRawEvidenceContract::BOUNDARY as $permission) self::assertFalse($permission);
    }

    public function testOnlyExactlyBoundDecoderProducesNormalizedResult(): void
    {
        $result = (new ProviderBoundEvidenceNormalizationService($this->root, new AgentMailProviderEvidenceDecoder()))->normalize($this->binding(), $this->raw(), $this->time('+1 minute'));
        self::assertSame(NormalizedToolResultContract::REQUIRED_FIELDS, array_keys($result));
        self::assertSame('ACCEPTED', $result['effect_outcome']['status']);
        self::assertSame(['provider_message_id' => 'msg-1', 'provider_thread_id' => 'thread-1'], $result['normalized_attributes']);
        self::assertSame(AgentMailProviderProfile::EVIDENCE_DECODER_ID, $result['decoder']['decoder_id']);
        self::assertFalse($result['recovery']['automatic_replay_permitted']);
    }

    public function testDecoderAndRawByteSubstitutionFailBeforeNormalization(): void
    {
        $binding = $this->binding();
        $binding['evidence_decoder']['id'] = 'substitute-decoder';
        $binding = self::seal($binding);
        try {
            (new ProviderBoundEvidenceNormalizationService($this->root, new AgentMailProviderEvidenceDecoder()))->normalize($binding, $this->raw(), $this->time('+1 minute'));
            self::fail('Decoder substitution accepted.');
        } catch (\RuntimeException $exception) { self::assertSame('GTP610_BOUND_DECODER_CONTEXT_INVALID', $exception->getMessage()); }

        $raw = $this->raw();
        $raw['content_base64'] = base64_encode($this->content().' ');
        $this->expectExceptionMessage('GTP610_BOUND_DECODER_CONTEXT_INVALID');
        (new ProviderBoundEvidenceNormalizationService($this->root, new AgentMailProviderEvidenceDecoder()))->normalize($this->binding(), $raw, $this->time('+1 minute'));
    }

    public function testLazarettoAdmitsNormalizedResultWithoutParsingRawProviderContent(): void
    {
        $result = (new ProviderBoundEvidenceNormalizationService($this->root, new AgentMailProviderEvidenceDecoder()))->normalize($this->binding(), $this->raw(), $this->time('+1 minute'));
        $admission = (new NormalizedToolResultAdmissionService($this->root))->admit($result, $this->time('+2 minutes'));
        self::assertSame('ADMITTED_NORMALIZED', $admission['status']);
        self::assertFalse($admission['raw_provider_content_interpreted']);
        self::assertFalse($admission['provider_reinvoked']);
    }

    public function testRawEvidenceCannotBypassNormalizationAndLiveAdmissionRemainsUntouched(): void
    {
        $this->expectExceptionMessage('GTP620_NORMALIZED_RESULT_NOT_ADMISSIBLE');
        (new NormalizedToolResultAdmissionService($this->root))->admit($this->raw(), $this->time('+2 minutes'));
    }

    public function testBatchSixDocumentsBatchSevenOnly(): void
    {
        $root = dirname(__DIR__, 3);
        $legacy = (string) file_get_contents($root.'/src/Imperium/Runtime/LaCortine/DeterministicLazarettoReceiptAdmissionService.php');
        self::assertStringNotContainsString('NormalizedToolResultAdmissionService', $legacy);
        $handoff = (string) file_get_contents($root.'/docs/handoffs/governed-tool-provider-separation-batch-6-complete.md');
        foreach (['Only Batch 7 may next be considered', 'Runtime behavior is unchanged', 'No provider was invoked', 'Batch 7 is not authorized'] as $proof) self::assertStringContainsString($proof, $handoff);
    }

    private function raw(): array
    {
        return (new ProviderNeutralRawEvidenceService($this->root))->preserve($this->binding(), $this->reference('email.send.v1', '1', 'tool/v1'), $this->reference('authorization-1', '2', 'authorization/v1'), $this->reference('execution-claim-1', '3', 'claim/v1'), 202, 'application/json', $this->content(), $this->time());
    }
    private function content(): string { return '{"message_id":"msg-1","thread_id":"thread-1"}'; }
    private function time(string $modify = ''): \DateTimeImmutable { $at = new \DateTimeImmutable('2026-08-29T02:30:00+00:00'); return '' === $modify ? $at : $at->modify($modify); }
    private function reference(string $id, string $digit, string $schema): array { return ['id' => $id, 'digest' => str_repeat($digit, 64), 'schema' => $schema]; }
    private function binding(): array
    {
        return self::seal(['schema' => 'imperium.la-cortine.provider-implementation-binding/v1', 'binding_id' => 'provider-implementation-binding-aaaaaaaaaaaaaaaaaaaa', 'instance_id' => 'imperium-test', 'source_authority' => $this->reference('authority-1', '4', 'authority/v1'), 'tool_operation' => $this->reference('email.send.v1', '1', 'tool/v1'), 'provider_implementation' => ['provider_id' => 'agentmail', 'adapter_id' => 'agentmail.email-send', 'adapter_version' => '1'], 'assurance_profile' => $this->reference('assurance-1', '5', 'assurance/v1'), 'credential_family' => ['family_id' => 'agentmail.api-key.v1', 'provider_id' => 'agentmail', 'secret_persistence_permitted' => false], 'request_encoder' => $this->reference('agentmail.email-send-request-encoder.v1', '6', 'encoder/v1'), 'evidence_decoder' => $this->reference(AgentMailProviderProfile::EVIDENCE_DECODER_ID, '7', 'decoder/v1'), 'destination_policy' => ['policy_id' => 'destination-1', 'policy_digest' => str_repeat('8', 64), 'exact_destination_required' => true], 'scope' => ['operation' => 'email.send', 'authorization_target_id' => 'authorization-1', 'authorization_target_digest' => str_repeat('2', 64), 'provider_substitution_permitted' => false], 'validity' => ['effective_at' => '2026-08-29T02:00:00+00:00', 'expires_at' => '2026-08-29T03:00:00+00:00'], 'status' => 'BOUND_INACTIVE', 'bound_at' => '2026-08-29T02:10:00+00:00', 'sealed' => true]);
    }
    private static function seal(array $record): array { unset($record['record_digest']); $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); return $record; }
}
