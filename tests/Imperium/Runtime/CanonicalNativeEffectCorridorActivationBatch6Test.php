<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\NativeEffect\CanonicalNativeEffectEvidenceSanitizer;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3).'/vendor/autoload.php';

final class CanonicalNativeEffectCorridorActivationBatch6Test extends TestCase
{
    private string $project;

    protected function setUp(): void
    {
        $this->project = dirname(__DIR__, 3);
    }

    public function testPackageIsFrozenBlockedAndContainsNoApprovedEffect(): void
    {
        $package = json_decode($this->read('docs/evidence/canonical-native-effect-live-trial-package-template-v1.json'), true, 64, JSON_THROW_ON_ERROR);
        self::assertSame('imperium.canonical-native-effect-live-trial-package/v1', $package['schema']);
        self::assertSame('BLOCKED_AWAITING_EXACT_BATCH_7_AUTHORIZATION', $package['status']);
        self::assertSame('AUTHORIZE_CANONICAL_NATIVE_EFFECT_LIVE_TRIAL_ONCE', $package['authorization']['required_operator_message_marker']);
        self::assertFalse($package['authorization']['marker_received']);
        self::assertFalse($package['authorization']['operation_approved']);
        self::assertFalse($package['authorization']['destination_approved']);
        self::assertNull($package['approved_effect']['operation']);
        self::assertNull($package['approved_effect']['destination']);
        foreach ($package['execution'] as $value) { self::assertFalse($value); }
    }

    public function testSanitizerProjectsOnlyDigestBoundNonAuthorizingEvidence(): void
    {
        $candidate = (new CanonicalNativeEffectEvidenceSanitizer())->sanitize($this->privateEvidence());
        self::assertSame(CanonicalNativeEffectEvidenceSanitizer::SCHEMA, $candidate['schema']);
        self::assertSame('email.send', $candidate['operation']);
        self::assertFalse($candidate['evidence_limits']['automatic_retry_permitted']);
        self::assertFalse($candidate['evidence_limits']['continuing_authority']);
        self::assertFalse($candidate['evidence_limits']['remote_cryptographic_authorship_proved']);
        self::assertSame('UNVERIFIED', $candidate['evidence_limits']['provider_side_idempotency_guarantee']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $candidate['record_digest']);
        $encoded = json_encode($candidate, JSON_THROW_ON_ERROR);
        foreach (['disposable@example.test', 'Bearer ', 'AGENTMAIL_API_KEY', 'var/imperium', 'https://'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $encoded);
        }
    }

    public function testSanitizerRejectsCredentialAdjacentAndPrivateMaterialRecursively(): void
    {
        $sanitizer = new CanonicalNativeEffectEvidenceSanitizer();
        foreach ([
            ['authorization_header' => 'redacted'],
            ['nested' => ['body' => 'private']],
            ['nested' => ['safe' => 'disposable@example.test']],
            ['nested' => ['safe' => 'C:\\private\\evidence.json']],
        ] as $injection) {
            try { $sanitizer->assertNoSensitiveMaterial($injection); self::fail('Expected CNE604_SENSITIVE_EVIDENCE_REFUSED'); }
            catch (\RuntimeException $error) { self::assertSame('CNE604_SENSITIVE_EVIDENCE_REFUSED', $error->getMessage()); }
        }
    }

    public function testBatchSixHasNoLiveCommandCredentialOrNetworkEdge(): void
    {
        $source = $this->read('src/Imperium/Runtime/NativeEffect/CanonicalNativeEffectEvidenceSanitizer.php');
        foreach (['file_put_contents', 'fopen(', 'getenv(', '$_ENV', '$_SERVER', 'CredentialBroker', 'AgentMail', 'HttpClient', 'curl_'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
        $commandSources = glob($this->project.'/src/Command/*.php') ?: [];
        foreach ($commandSources as $path) {
            self::assertStringNotContainsString('imperium:canonical-native-effect:live-trial-once', file_get_contents($path));
        }
        self::assertFileDoesNotExist($this->project.'/docs/evidence/canonical-native-effect-live-trial-1-sanitized.json');
        self::assertFileDoesNotExist($this->project.'/var/imperium/runtime/canonical-native-effect-admissions');
    }

    public function testRunbookAndHandoffPreserveTheBatchSevenAndEightStops(): void
    {
        $runbook = $this->read('docs/canonical-native-effect-corridor-activation-batch-6-live-trial-package-v1.md');
        $handoff = $this->read('docs/handoffs/canonical-native-effect-corridor-activation-batch-6-complete-batch-7-blocked.md');
        foreach (['do not run', 'command does not exist', 'UNKNOWN', 'Batch 8', 'php vendor/bin/phpunit tests'] as $evidence) {
            self::assertStringContainsStringIgnoringCase($evidence, $runbook);
        }
        foreach (['BATCH_7_BLOCKED', 'AUTHORIZE_CANONICAL_NATIVE_EFFECT_LIVE_TRIAL_ONCE', 'email.send', 'Batch 8'] as $gate) {
            self::assertStringContainsString($gate, $handoff);
        }
        $ledger = $this->read('docs/canonical-native-effect-corridor-activation-implementation-ledger-v1.md');
        foreach (['Preparation Batch 0', 'Batch 6', '2,189 tests', '48,255 assertions', 'Batch 7 — blocked', 'Batch 8 — blocked', 'php vendor/bin/phpunit tests'] as $entry) {
            self::assertStringContainsString($entry, $ledger);
        }
    }

    private function privateEvidence(): array
    {
        $references = [];
        foreach (CanonicalNativeEffectEvidenceSanitizer::REFERENCE_NAMES as $index => $name) {
            $references[$name] = [
                'schema' => 'imperium.synthetic-reference/v1',
                'id_digest' => str_repeat(dechex(($index + 1) % 16), 64),
                'record_digest' => str_repeat(dechex(($index + 2) % 16), 64),
            ];
        }
        return [
            'source_commit' => str_repeat('a', 40),
            'runtime' => ['php_version' => PHP_VERSION, 'os_family' => PHP_OS_FAMILY],
            'operation' => 'email.send',
            'destination_digest' => str_repeat('b', 64),
            'payload_digest' => str_repeat('c', 64),
            'idempotency_key_digest' => str_repeat('d', 64),
            'authorization_marker_digest' => CanonicalNativeEffectEvidenceSanitizer::REQUIRED_AUTHORIZATION_MARKER_DIGEST,
            'references' => $references,
            'provider_outcome' => ['classification' => 'ACCEPTED', 'http_status' => 202, 'message_id_digest' => str_repeat('e', 64), 'thread_id_digest' => str_repeat('f', 64)],
            'timing' => ['effect_started_at' => 100, 'provider_observed_at' => 101, 'provider_received_at' => 102, 'receipt_bound_at' => 103],
        ];
    }

    private function read(string $path): string
    {
        return str_replace("\r\n", "\n", (string) file_get_contents($this->project.'/'.$path));
    }
}
