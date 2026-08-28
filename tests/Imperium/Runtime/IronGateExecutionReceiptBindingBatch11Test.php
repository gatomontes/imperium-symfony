<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clavium\DeterministicJournalBoundCredentialBroker;
use App\Imperium\Runtime\Curia\OutboundEmailAuthorizationRequestService;
use App\Imperium\Runtime\Imperator\OutboundEmailAuthorizationIssuanceService;
use App\Imperium\Runtime\Imperator\OutboundEmailDecisionService;
use App\Imperium\Runtime\LaCortine\AgentMailIdempotencyHeaderAdapter;
use App\Imperium\Runtime\LaCortine\CredentialBroker;
use App\Imperium\Runtime\LaCortine\CredentialCapability;
use App\Imperium\Runtime\LaCortine\DeterministicEffectStartJournalService;
use App\Imperium\Runtime\LaCortine\DeterministicExecutionClaimService;
use App\Imperium\Runtime\LaCortine\DeterministicLazarettoReceiptAdmissionService;
use App\Imperium\Runtime\LaCortine\DeterministicRawProviderResultService;
use App\Imperium\Runtime\LaCortine\DeterministicReceiptReconstructionService;
use PHPUnit\Framework\TestCase;

final class IronGateExecutionReceiptBindingBatch11Test extends TestCase
{
    private string $root;
    private string $bindingId = 'curia-seneschal-binding-fedcba9876543210fedc';

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-iron-gate-batch-11-'.bin2hex(random_bytes(5));
        $record = ['schema' => 'imperium.curia-seneschal-occupancy/v1', 'binding_id' => $this->bindingId, 'instance_id' => 'imperium-test', 'office' => 'curia', 'seat' => 'curia.seneschal', 'manifestation_id' => 'manifestation-seneschal-test', 'occupancy_generation' => 1, 'status' => 'ACTIVE', 'outbound_email_request_authority' => true, 'execution_authority' => false, 'sealed' => true];
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $directory = $this->root.'/var/imperium/offices/curia/occupancy';
        mkdir($directory, 0770, true);
        file_put_contents($directory.'/'.$this->bindingId.'.json', json_encode($record, JSON_THROW_ON_ERROR));
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->root);
    }

    public function testCampaignCloseoutNamesTerminalEvidenceAndPreservesDeferredBoundaries(): void
    {
        $project = dirname(__DIR__, 3);
        $audit = (string) file_get_contents($project.'/docs/iron-gate-execution-receipt-binding-terminal-evidence-audit.md');
        $handoff = (string) file_get_contents($project.'/docs/handoffs/iron-gate-execution-receipt-binding-campaign-complete.md');

        foreach (['`TERMINAL_THROUGH_BATCH_11`', '`UNKNOWN_REPLAY_PROHIBITED`', '`DURABLE_RECEIPT_BOUND`', '`DEFERRED_BOUNDARY`', '`UNMIGRATED_LIVE_CONSUMER`', '`SEPARATE_SORTIE_CAMPAIGN`'] as $evidence) {
            self::assertStringContainsString($evidence, $audit);
        }
        foreach (['No batches remain', 'not a live-provider rollout', 'perform no', 'network I/O', 'There is no Iron Gate Execution Authority and Receipt Binding Batch 12'] as $limit) {
            self::assertStringContainsString($limit, $handoff);
        }
    }

    public function testCompleteAcceptedPathReconstructsWithoutAuthorityOrExternalSideEffects(): void
    {
        $bytes = '{"message_id":"message-final","thread_id":"thread-final"}';
        [$issuance, $claim, $journal, , $envelope] = $this->throughObservedProviderResponse($bytes);
        $result = (new DeterministicRawProviderResultService($this->root))->seal($envelope['envelope_id']);
        $binding = (new DeterministicLazarettoReceiptAdmissionService($this->root))->admit($result['result_id'], $this->time('+7 minutes'));
        $before = $this->recordFiles();
        $proof = (new DeterministicReceiptReconstructionService($this->root))->reconstruct($binding['binding_id']);

        self::assertSame($issuance['issued_authorization'], $proof['source_authorization']);
        self::assertSame($claim, $proof['execution_claim']);
        self::assertSame($result, $proof['raw_provider_result']);
        self::assertSame($binding, $proof['receipt_binding']);
        foreach (['curia_occupancy', 'source_request', 'source_decision', 'authorization_issuance', 'effect_start_journal', 'provider_invocation_admission', 'credential_consumption_attempt', 'provider_callback_start', 'provider_response_envelope'] as $link) self::assertIsArray($proof[$link]);
        self::assertFalse($proof['provider_reinvoked']);
        self::assertFalse($proof['credential_resolved']);
        self::assertFalse($proof['external_io_performed']);
        self::assertSame($before, $this->recordFiles(), 'Read-only reconstruction must create no file.');
        self::assertSame('UNKNOWN_REPLAY_PROHIBITED', $journal['effect']['outcome']);
        self::assertSame('COMPLETE', $binding['recovery']['checkpoint']);
        $this->assertSecretExcluded('batch-11-secret-material');
    }

    public function testCrashBeforeProviderAdmissionRemainsUnknownAndNonReplayable(): void
    {
        [, , $journal] = $this->throughJournal();
        self::assertSame('UNKNOWN_REPLAY_PROHIBITED', $journal['effect']['outcome']);
        self::assertFalse($journal['provider_safety']['automatic_replay_permitted']);
        self::assertSame([], glob($this->root.'/'.DeterministicJournalBoundCredentialBroker::ADMISSIONS.'/*.json') ?: []);
        self::assertSame([], glob($this->root.'/'.DeterministicRawProviderResultService::RESULTS.'/*.json') ?: []);
    }

    public function testCrashAfterProviderAdmissionRetainsUnknownAndProhibitsSecondCallback(): void
    {
        [, , $journal, $admission] = $this->throughProviderAdmission();
        self::assertSame('NOT_ATTEMPTED', $admission['provider_request']['outcome']);
        self::assertFalse($admission['provider_request']['provider_callback_may_have_run']);
        $callbackStarts = glob($this->root.'/'.DeterministicJournalBoundCredentialBroker::CALLBACK_STARTS.'/*.json') ?: [];
        self::assertCount(1, $callbackStarts);
        $callbackStart = json_decode((string) file_get_contents($callbackStarts[0]), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($callbackStart['state']['provider_callback_may_have_run']);
        self::assertSame('UNKNOWN_REPLAY_PROHIBITED', $callbackStart['state']['outcome']);
        self::assertSame([], glob($this->root.'/'.DeterministicRawProviderResultService::RESULTS.'/*.json') ?: []);

        $capability = $this->capability();
        $this->expectExceptionMessage('IGB615_PROVIDER_INVOCATION_REPLAY_PROHIBITED');
        (new DeterministicJournalBoundCredentialBroker($this->root, $this->credentials($capability), new AgentMailIdempotencyHeaderAdapter()))->invoke($journal['journal_id'], $capability, $this->payload(), $this->time('+6 minutes'), static fn (): array => []);
    }

    public function testTamperedFinalBindingCannotBeReconstructed(): void
    {
        [, , , , $envelope] = $this->throughObservedProviderResponse('{"message_id":"message-final","thread_id":"thread-final"}');
        $result = (new DeterministicRawProviderResultService($this->root))->seal($envelope['envelope_id']);
        $binding = (new DeterministicLazarettoReceiptAdmissionService($this->root))->admit($result['result_id'], $this->time('+7 minutes'));
        $path = $this->root.'/'.DeterministicLazarettoReceiptAdmissionService::BINDINGS.'/'.$binding['binding_id'].'.json';
        $tampered = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $tampered['provider_outcome']['status'] = 'REJECTED';
        file_put_contents($path, json_encode($tampered, JSON_THROW_ON_ERROR));

        $this->expectExceptionMessage('IGX902_RECEIPT_BINDING_INVALID');
        (new DeterministicReceiptReconstructionService($this->root))->reconstruct($binding['binding_id']);
    }

    public function testTamperedCallbackCheckpointBreaksCompleteReconstruction(): void
    {
        [, , , , $envelope] = $this->throughObservedProviderResponse('{"message_id":"message-final","thread_id":"thread-final"}');
        $result = (new DeterministicRawProviderResultService($this->root))->seal($envelope['envelope_id']);
        $binding = (new DeterministicLazarettoReceiptAdmissionService($this->root))->admit($result['result_id'], $this->time('+7 minutes'));
        $path = (glob($this->root.'/'.DeterministicJournalBoundCredentialBroker::CALLBACK_STARTS.'/*.json') ?: [])[0];
        $tampered = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $tampered['state']['provider_callback_may_have_run'] = false;
        file_put_contents($path, json_encode($tampered, JSON_THROW_ON_ERROR));
        $this->expectExceptionMessage('IGX907_PROVIDER_CALLBACK_START_INVALID');
        (new DeterministicReceiptReconstructionService($this->root))->reconstruct($binding['binding_id']);
    }

    private function throughProviderAdmission(): array
    {
        [$issuance, $claim, $journal] = $this->throughJournal();
        $capability = $this->capability();
        (new DeterministicJournalBoundCredentialBroker($this->root, $this->credentials($capability), new AgentMailIdempotencyHeaderAdapter()))->invoke($journal['journal_id'], $capability, $this->payload(), $this->time('+5 minutes'), static fn (): array => ['observed_in_memory' => true]);
        $paths = glob($this->root.'/'.DeterministicJournalBoundCredentialBroker::ADMISSIONS.'/*.json') ?: [];
        self::assertCount(1, $paths);
        $admission = json_decode((string) file_get_contents($paths[0]), true, 512, JSON_THROW_ON_ERROR);
        return [$issuance, $claim, $journal, $admission];
    }

    private function throughObservedProviderResponse(string $body): array
    {
        [$issuance, $claim, $journal] = $this->throughJournal();
        $capability = $this->capability();
        (new DeterministicJournalBoundCredentialBroker($this->root, $this->credentials($capability), new AgentMailIdempotencyHeaderAdapter()))->invoke($journal['journal_id'], $capability, $this->payload(), $this->time('+5 minutes'), fn (): array => ['http_status' => 200, 'headers' => ['Content-Type' => 'application/json'], 'body' => $body, 'observed_at' => $this->time('+6 minutes')->format(DATE_ATOM), 'received_at' => $this->time('+6 minutes')->format(DATE_ATOM)]);
        $admissionPaths = glob($this->root.'/'.DeterministicJournalBoundCredentialBroker::ADMISSIONS.'/*.json') ?: [];
        $envelopePaths = glob($this->root.'/'.DeterministicJournalBoundCredentialBroker::RESPONSE_ENVELOPES.'/*.json') ?: [];
        self::assertCount(1, $admissionPaths);
        self::assertCount(1, $envelopePaths);
        return [$issuance, $claim, $journal, json_decode((string) file_get_contents($admissionPaths[0]), true, 512, JSON_THROW_ON_ERROR), json_decode((string) file_get_contents($envelopePaths[0]), true, 512, JSON_THROW_ON_ERROR)];
    }

    private function throughJournal(): array
    {
        $request = (new OutboundEmailAuthorizationRequestService($this->root))->request($this->bindingId, $this->holder(), 'Send the sealed operational notice', $this->scope(), $this->providerSafety(), $this->time('+10 minutes'), $this->time());
        $decision = (new OutboundEmailDecisionService($this->root))->decide($request['request_id'], 'AUTHORIZED', 'Exact act approved.', 'No scope widening.', $this->time('+8 minutes'), $this->time('+1 minute'));
        $issuance = (new OutboundEmailAuthorizationIssuanceService($this->root))->issue($decision['decision_id'], $this->time('+2 minutes'));
        $claim = (new DeterministicExecutionClaimService($this->root))->claim($issuance['issuance_id'], $this->capability(), $this->time('+3 minutes'));
        $journal = (new DeterministicEffectStartJournalService($this->root))->start($claim['claim_id'], $this->time('+4 minutes'));
        return [$issuance, $claim, $journal];
    }

    private function holder(): array
    {
        return ['actor_id' => 'agentmail-transport', 'office' => 'la-cortine', 'seat' => 'la-cortine.deterministic-boundary-executor', 'binding_id' => 'deterministic-email-lane', 'runtime_principal_id' => 'agentmail-email-send-command'];
    }

    private function scope(): array
    {
        return ['operation' => 'email.send', 'commission_id' => 'commission-final', 'inbox_id' => 'inbox-final', 'destination' => 'https://api.agentmail.to/v0/inboxes/inbox-final/messages/send', 'recipient_set_digest' => hash('sha256', 'recipient'), 'subject_digest' => hash('sha256', 'subject'), 'body_digest' => hash('sha256', 'body'), 'attachment_manifest_digest' => hash('sha256', 'attachments'), 'payload_digest' => hash('sha256', $this->payload()), 'credential_reference_digest' => hash('sha256', 'credential-reference-final'), 'expected_return_contract' => 'agentmail.message/v1'];
    }

    private function providerSafety(): array
    {
        return ['strategy' => 'PROVIDER_IDEMPOTENCY_KEY', 'provider' => 'agentmail', 'endpoint' => 'https://api.agentmail.to/v0/inboxes/inbox-final/messages/send', 'idempotency_key' => 'iron-gate-final-key', 'idempotency_key_digest' => hash('sha256', 'iron-gate-final-key'), 'request_fingerprint' => hash('sha256', 'final-request'), 'provider_contract_reference' => 'docs/runtime/agentmail-email-transport.md', 'provider_key_expires_at' => $this->time('+15 minutes')->format(DATE_ATOM)];
    }

    private function capability(): CredentialCapability
    {
        return new CredentialCapability('credential-capability.final', 'credential-reference-final', 'commission-final', 'email.send', $this->time('+7 minutes'));
    }

    private function payload(): string
    {
        return '{"to":["final@example.test"],"subject":"Final","text":"Evidence"}';
    }

    private function credentials(CredentialCapability $expected): CredentialBroker
    {
        return new class($expected) implements CredentialBroker {
            private bool $used = false;
            public function __construct(private CredentialCapability $expected) {}
            public function issue(string $credentialRef, string $commissionId, string $operation, \DateTimeImmutable $expiresAt, int $maxUses = 1): CredentialCapability { throw new \LogicException('Not permitted.'); }
            public function consume(CredentialCapability $capability, callable $providerOperation): mixed
            {
                if ($capability->metadata() !== $this->expected->metadata() || $this->used) throw new \RuntimeException('TEST_CAPABILITY_INVALID');
                $this->used = true;
                return $providerOperation('batch-11-secret-material');
            }
        };
    }

    private function time(string $modifier = ''): \DateTimeImmutable
    {
        $time = new \DateTimeImmutable('2035-01-01T00:00:00+00:00');
        return '' === $modifier ? $time : $time->modify($modifier);
    }

    private function recordFiles(): array
    {
        $files = [];
        if (!is_dir($this->root.'/var/imperium')) return [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root.'/var/imperium')) as $file) if ($file->isFile()) $files[] = $file->getPathname();
        sort($files, SORT_STRING);
        return $files;
    }

    private function assertSecretExcluded(string $secret): void
    {
        foreach ($this->recordFiles() as $path) self::assertStringNotContainsString($secret, (string) file_get_contents($path), $path);
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) return;
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->removeTree($child) : unlink($child);
        }
        rmdir($path);
    }
}
