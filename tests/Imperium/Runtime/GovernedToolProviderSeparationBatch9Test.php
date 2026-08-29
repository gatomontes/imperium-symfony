<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\BoundProviderEvidenceDecoder;
use App\Imperium\Runtime\LaCortine\NormalizedToolResultAdmissionService;
use App\Imperium\Runtime\LaCortine\ProviderBoundEvidenceNormalizationService;
use App\Imperium\Runtime\LaCortine\ProviderEvidenceDecoderContract;
use App\Imperium\Runtime\LaCortine\ProviderNeutralRawEvidenceService;
use PHPUnit\Framework\TestCase;

final class GovernedToolProviderSeparationBatch9Test extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-provider-separation-audit-'.bin2hex(random_bytes(5));
        mkdir($this->root, 0770, true);
    }

    protected function tearDown(): void
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        rmdir($this->root);
    }

    public function testSterileSecondAdapterPreservesToolAuthorityAndGenericAdmission(): void
    {
        $binding = $this->binding();
        $raw = $this->raw($binding, '{"delivery_id":"sterile-1"}');
        $result = (new ProviderBoundEvidenceNormalizationService($this->root, $this->decoder()))->normalize($binding, $raw, $this->time('+1 minute'));
        $admission = (new NormalizedToolResultAdmissionService($this->root))->admit($result, $this->time('+2 minutes'));

        self::assertSame($binding['tool_operation'], $result['tool_operation']);
        self::assertSame('sterile.delivery-receipt-decoder.v1', $result['decoder']['decoder_id']);
        self::assertSame(['provider_delivery_id' => 'sterile-1'], $result['normalized_attributes']);
        self::assertSame('ADMITTED_NORMALIZED', $admission['status']);
    }

    public function testProviderOrDecoderSubstitutionFailsBeforeNormalization(): void
    {
        $binding = $this->binding();
        $raw = $this->raw($binding, '{"delivery_id":"sterile-1"}');
        $binding['provider_implementation']['provider_id'] = 'substitute';
        $binding = self::seal($binding);

        $this->expectExceptionMessage('GTP610_BOUND_DECODER_CONTEXT_INVALID');
        (new ProviderBoundEvidenceNormalizationService($this->root, $this->decoder()))->normalize($binding, $raw, $this->time('+1 minute'));
    }

    public function testTamperAndChangedContentCannotCollideWithTheOriginalResult(): void
    {
        $binding = $this->binding();
        $firstRaw = $this->raw($binding, '{"delivery_id":"sterile-1"}');
        $secondRaw = $this->raw($binding, '{"delivery_id":"sterile-2"}');
        $normalizer = new ProviderBoundEvidenceNormalizationService($this->root, $this->decoder());
        $first = $normalizer->normalize($binding, $firstRaw, $this->time('+1 minute'));
        $same = $normalizer->normalize($binding, $firstRaw, $this->time('+1 minute'));
        $second = $normalizer->normalize($binding, $secondRaw, $this->time('+1 minute'));

        self::assertSame($first['result_id'], $same['result_id']);
        self::assertNotSame($first['result_id'], $second['result_id']);

        $firstRaw['content_base64'] = base64_encode('{"delivery_id":"tampered"}');
        $this->expectExceptionMessage('GTP610_BOUND_DECODER_CONTEXT_INVALID');
        $normalizer->normalize($binding, $firstRaw, $this->time('+1 minute'));
    }

    public function testUnknownOutcomeCannotAuthorizeReplayOrProviderReinvocation(): void
    {
        $binding = $this->binding();
        $result = (new ProviderBoundEvidenceNormalizationService($this->root, $this->decoder()))->normalize($binding, $this->raw($binding, '{"delivery_id":"sterile-1"}'), $this->time('+1 minute'));
        $result['effect_outcome']['status'] = 'UNKNOWN_REPLAY_PROHIBITED';
        $result['recovery']['automatic_replay_permitted'] = true;
        $result = self::seal($result);

        $this->expectExceptionMessage('GTP620_NORMALIZED_RESULT_NOT_ADMISSIBLE');
        (new NormalizedToolResultAdmissionService($this->root))->admit($result, $this->time('+2 minutes'));
    }

    public function testPersistedSeparatedEvidenceExcludesCredentialSecrets(): void
    {
        $binding = $this->binding();
        $result = (new ProviderBoundEvidenceNormalizationService($this->root, $this->decoder()))->normalize($binding, $this->raw($binding, '{"delivery_id":"sterile-1"}'), $this->time('+1 minute'));
        (new NormalizedToolResultAdmissionService($this->root))->admit($result, $this->time('+2 minutes'));

        $persisted = '';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $item) if ($item->isFile()) $persisted .= (string) file_get_contents($item->getPathname());
        self::assertStringNotContainsString('terminal-audit-secret', $persisted);
        self::assertStringNotContainsString('Authorization', $persisted);
    }

    public function testTerminalAuditKeepsExecutionPausedAndAuthorizesNothing(): void
    {
        $root = dirname(__DIR__, 3);
        $audit = (string) file_get_contents($root.'/docs/governed-tool-provider-separation-terminal-evidence-audit.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/governed-tool-provider-separation-campaign-complete.md');
        foreach (['SEPARATION_CANONICAL_EXECUTION_NOT_AUTHORIZED', 'Provider Execution Assurance may **not** resume', 'provider-binding activation', 'cross-process opaque capability custodian'] as $proof) self::assertStringContainsString($proof, $audit);
        foreach (['authorizes no implementation batch', 'no provider call', 'Do not infer authorization'] as $boundary) self::assertStringContainsString($boundary, $handoff);
    }

    private function raw(array $binding, string $content): array
    {
        return (new ProviderNeutralRawEvidenceService($this->root))->preserve($binding, $binding['tool_operation'], $this->reference('authorization-sterile', '2', 'authorization/v1'), $this->reference('execution-sterile', '3', 'claim/v1'), 202, 'application/json', $content, $this->time());
    }

    private function decoder(): BoundProviderEvidenceDecoder
    {
        return new class implements BoundProviderEvidenceDecoder {
            public function supports(array $binding): bool
            {
                return 'sterile-mail' === ($binding['provider_implementation']['provider_id'] ?? null)
                    && 'sterile.delivery-receipt-decoder.v1' === ($binding['evidence_decoder']['id'] ?? null);
            }

            public function decode(array $binding, array $rawResult, string $rawContent, \DateTimeImmutable $decodedAt): array
            {
                $decoded = json_decode($rawContent, true, 512, JSON_THROW_ON_ERROR);
                $record = ['schema' => ProviderEvidenceDecoderContract::SCHEMA, 'decoder_id' => 'sterile.delivery-receipt-decoder.v1', 'decoder_version' => '1', 'provider_binding' => ['id' => $binding['binding_id'], 'digest' => $binding['record_digest'], 'schema' => $binding['schema']], 'raw_provider_result' => $rawResult, 'normalized_result_contract' => 'email.send-result/v1', 'normalized_attributes' => ['provider_delivery_id' => $decoded['delivery_id']], 'decoded_at' => $decodedAt->format(DATE_ATOM), 'sealed' => true];
                return GovernedToolProviderSeparationBatch9Test::seal($record);
            }
        };
    }

    private function binding(): array
    {
        return self::seal(['schema' => 'imperium.la-cortine.provider-implementation-binding/v1', 'binding_id' => 'provider-implementation-binding-99999999999999999999', 'instance_id' => 'imperium-test', 'source_authority' => $this->reference('authority-sterile', '1', 'authority/v1'), 'tool_operation' => $this->reference('email.send.v1', '0', 'imperium.armory.governed-tool-operation/v1'), 'provider_implementation' => ['provider_id' => 'sterile-mail', 'adapter_id' => 'sterile.email-send', 'adapter_version' => '1'], 'assurance_profile' => $this->reference('sterile-assurance', '4', 'assurance/v1'), 'credential_family' => ['family_id' => 'sterile.credential.v1', 'provider_id' => 'sterile-mail', 'secret_persistence_permitted' => false], 'request_encoder' => $this->reference('sterile.encoder.v1', '5', 'encoder/v1'), 'evidence_decoder' => $this->reference('sterile.delivery-receipt-decoder.v1', '6', 'decoder/v1'), 'destination_policy' => ['policy_id' => 'sterile-destination', 'policy_digest' => str_repeat('7', 64), 'exact_destination_required' => true], 'scope' => ['operation' => 'email.send', 'authorization_target_id' => 'authorization-sterile', 'authorization_target_digest' => str_repeat('2', 64), 'provider_substitution_permitted' => false], 'validity' => ['effective_at' => $this->time('-1 minute')->format(DATE_ATOM), 'expires_at' => $this->time('+5 minutes')->format(DATE_ATOM)], 'status' => 'BOUND_INACTIVE', 'bound_at' => $this->time('-1 minute')->format(DATE_ATOM), 'sealed' => true]);
    }

    private function reference(string $id, string $digit, string $schema): array { return ['id' => $id, 'digest' => str_repeat($digit, 64), 'schema' => $schema]; }
    private function time(string $modify = ''): \DateTimeImmutable { $at = new \DateTimeImmutable('2026-08-29T04:00:00+00:00'); return '' === $modify ? $at : $at->modify($modify); }
    public static function seal(array $record): array { unset($record['record_digest']); $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); return $record; }
}
