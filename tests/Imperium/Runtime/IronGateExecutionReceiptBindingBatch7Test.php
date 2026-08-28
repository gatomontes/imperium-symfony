<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clavium\DeterministicJournalBoundCredentialBroker;
use App\Imperium\Runtime\LaCortine\AgentMailIdempotencyHeaderAdapter;
use App\Imperium\Runtime\LaCortine\CredentialBroker;
use App\Imperium\Runtime\LaCortine\CredentialCapability;
use App\Imperium\Runtime\LaCortine\DeterministicEffectStartJournalContract;
use App\Imperium\Runtime\LaCortine\DeterministicEffectStartJournalService;
use App\Imperium\Runtime\LaCortine\DeterministicExecutionClaimContract;
use App\Imperium\Runtime\LaCortine\DeterministicExecutionClaimService;
use App\Imperium\Runtime\LaCortine\DeterministicRawProviderResultContract;
use App\Imperium\Runtime\LaCortine\DeterministicRawProviderResultService;
use PHPUnit\Framework\TestCase;

final class IronGateExecutionReceiptBindingBatch7Test extends TestCase
{
    private string $root;
    private string $claimId = 'deterministic-execution-claim-0123456789abcdef0123';

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-iron-gate-batch-7-'.bin2hex(random_bytes(5));
        $this->writeClaim();
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->root);
    }

    public function testEffectStartJournalBindsOneWinnerAndDefaultsToUnknownReplayProhibited(): void
    {
        $service = new DeterministicEffectStartJournalService($this->root);
        $journal = $service->start($this->claimId, $this->time('+1 minute'));

        self::assertSame(DeterministicEffectStartJournalContract::REQUIRED_FIELDS, array_keys($journal));
        self::assertSame($this->claimId, $journal['execution_claim']['id']);
        self::assertSame('PROVIDER_IDEMPOTENCY_KEY', $journal['provider_safety']['strategy']);
        self::assertFalse($journal['provider_safety']['automatic_replay_permitted']);
        self::assertSame('EFFECT_STARTED', $journal['effect']['checkpoint']);
        self::assertTrue($journal['effect']['external_io_may_have_started']);
        self::assertSame('UNKNOWN_REPLAY_PROHIBITED', $journal['effect']['outcome']);
        self::assertFalse($journal['effect']['provider_invoked_by_transition']);
        self::assertTrue($journal['credential_use']['consumption_required']);
        self::assertFalse($journal['credential_use']['consumed_by_journal']);
        self::assertFalse($journal['credential_use']['credential_resolved']);
        self::assertSame($journal, $service->start($this->claimId, $this->time('+2 minutes')));
        self::assertFalse(is_dir($this->root.'/var/imperium/lazaretto'));
    }

    public function testExpiredClaimCannotOpenEffectStart(): void
    {
        $this->expectExceptionMessage('IGJ502_EXECUTION_CLAIM_NOT_STARTABLE');
        (new DeterministicEffectStartJournalService($this->root))->start($this->claimId, $this->time('+6 minutes'));
    }

    public function testTamperedClaimAndCredentialSecretsFailClosed(): void
    {
        $path = $this->root.'/'.DeterministicExecutionClaimService::CLAIMS.'/'.$this->claimId.'.json';
        $tampered = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $tampered['request']['destination'] = '/attacker';
        file_put_contents($path, json_encode($tampered, JSON_THROW_ON_ERROR));

        self::assertStringNotContainsString('test-secret-material', (string) file_get_contents($path));
        $this->expectExceptionMessage('IGJ502_EXECUTION_CLAIM_NOT_STARTABLE');
        (new DeterministicEffectStartJournalService($this->root))->start($this->claimId, $this->time('+1 minute'));
    }

    public function testJournalBoundBrokerPropagatesExactIdempotencyHeaderOnce(): void
    {
        $journal = (new DeterministicEffectStartJournalService($this->root))->start($this->claimId, $this->time('+1 minute'));
        $capability = new CredentialCapability('credential-capability.test', 'credential-reference-only', 'commission-test', 'email.send', $this->time('+5 minutes'));
        $credentials = $this->credentials($capability);
        $providerCalls = 0;
        $providerRequest = null;
        $result = (new DeterministicJournalBoundCredentialBroker($this->root, $credentials, new AgentMailIdempotencyHeaderAdapter()))->invoke(
            $journal['journal_id'],
            $capability,
            $this->payload(),
            $this->time('+2 minutes'),
            function (array $request) use (&$providerCalls, &$providerRequest): array {
                ++$providerCalls;
                $providerRequest = $request;
                return ['accepted_in_memory' => true];
            },
        );

        self::assertSame(['accepted_in_memory' => true], $result);
        self::assertSame(1, $providerCalls);
        self::assertSame('iron-gate-test-key', $providerRequest['headers']['Idempotency-Key']);
        self::assertSame('Bearer test-secret-material', $providerRequest['headers']['Authorization']);
        self::assertSame($this->payload(), $providerRequest['body']);
        $admissions = glob($this->root.'/'.DeterministicJournalBoundCredentialBroker::ADMISSIONS.'/*.json') ?: [];
        self::assertCount(1, $admissions);
        self::assertStringNotContainsString('test-secret-material', (string) file_get_contents($admissions[0]));

        try {
            (new DeterministicJournalBoundCredentialBroker($this->root, $credentials, new AgentMailIdempotencyHeaderAdapter()))->invoke($journal['journal_id'], $capability, $this->payload(), $this->time('+3 minutes'), static fn (): array => []);
            self::fail('A durable provider admission must prohibit a second callback.');
        } catch (\RuntimeException $exception) {
            self::assertSame('IGB615_PROVIDER_INVOCATION_REPLAY_PROHIBITED', $exception->getMessage());
        }
        self::assertSame(1, $providerCalls);
    }

    public function testProviderCallbackIsUnreachableWithoutStoredExactJournal(): void
    {
        $capability = new CredentialCapability('credential-capability.test', 'credential-reference-only', 'commission-test', 'email.send', $this->time('+5 minutes'));
        $credentials = $this->credentials($capability);
        $called = false;

        try {
            (new DeterministicJournalBoundCredentialBroker($this->root, $credentials, new AgentMailIdempotencyHeaderAdapter()))->invoke('deterministic-effect-start-journal-0123456789abcdef0123', $capability, $this->payload(), $this->time('+2 minutes'), function () use (&$called): void { $called = true; });
            self::fail('A provider callback must not be reachable without its journal.');
        } catch (\RuntimeException $exception) {
            self::assertSame('IGB611_EFFECT_START_JOURNAL_ABSENT', $exception->getMessage());
        }
        self::assertFalse($called);
    }

    public function testObservedAcceptedResponseSealsRawReceiptAndExactProviderIdentity(): void
    {
        $admission = $this->invokeOnce();
        $bytes = '{"message_id":"message-test","thread_id":"thread-test"}';
        $service = new DeterministicRawProviderResultService($this->root);
        $result = $service->seal($admission['admission_id'], 200, $bytes, $this->time('+3 minutes'), $this->time('+3 minutes'));

        self::assertSame(DeterministicRawProviderResultContract::REQUIRED_FIELDS, array_keys($result));
        self::assertSame('ACCEPTED', $result['provider_outcome']['status']);
        self::assertSame(['message_id' => 'message-test', 'thread_id' => 'thread-test'], $result['provider_outcome']['provider_receipt_identity']);
        self::assertSame(hash('sha256', $bytes), $result['raw_receipt']['content_digest']);
        self::assertSame($bytes, base64_decode($result['raw_receipt']['content_base64'], true));
        self::assertSame('RAW_RECEIPT_SEALED', $result['recovery']['checkpoint']);
        self::assertFalse($result['recovery']['automatic_replay_permitted']);
        self::assertFalse($result['recovery']['provider_reinvoked']);
        self::assertSame($result, $service->seal($admission['admission_id'], 200, $bytes, $this->time('+4 minutes'), $this->time('+4 minutes')));
        self::assertFalse(is_dir($this->root.'/var/imperium/lazaretto'));
    }

    public function testObservedRejectionIsNotMisreportedAsAcceptance(): void
    {
        $admission = $this->invokeOnce();
        $result = (new DeterministicRawProviderResultService($this->root))->seal($admission['admission_id'], 422, '{"error":"rejected"}', $this->time('+3 minutes'), $this->time('+3 minutes'));

        self::assertSame('REJECTED', $result['provider_outcome']['status']);
        self::assertNull($result['provider_outcome']['provider_receipt_identity']);
        self::assertSame('RAW_RECEIPT_SEALED', $result['recovery']['checkpoint']);
    }

    private function writeClaim(): void
    {
        $record = [
            'schema' => DeterministicExecutionClaimContract::SCHEMA,
            'claim_id' => $this->claimId,
            'instance_id' => 'imperium-test',
            'source_authorization' => ['id' => 'outbound-email-authorization-0123456789abcdef0123', 'digest' => hash('sha256', 'authorization'), 'schema' => 'imperium.la-cortine.deterministic-outbound-email-authorization/v1', 'issuer' => ['actor_id' => 'imperator', 'office' => 'imperator', 'seat' => 'imperator', 'binding_id' => 'imperator', 'runtime_principal_id' => 'imperator'], 'decision_owner' => ['kind' => 'imperator', 'id' => 'imperator-development-root']],
            'authorization_consumption' => ['authority_id' => 'outbound-email-authorization-0123456789abcdef0123', 'source_digest' => hash('sha256', 'authorization'), 'consumed_at' => $this->time('-1 minute')->format(DATE_ATOM), 'consumed' => true, 'continuing_authority' => false],
            'request' => ['id' => 'outbound-email-request-0123456789abcdef0123', 'commission_id' => 'commission-test', 'authorization_id' => 'outbound-email-authorization-0123456789abcdef0123', 'authorization_digest' => hash('sha256', 'authorization'), 'mode' => 'DETERMINISTIC', 'operation' => 'email.send', 'destination' => 'https://api.agentmail.to/v0/inboxes/test/messages/send', 'payload_digest' => hash('sha256', $this->payload()), 'expected_return_contract' => 'agentmail.message/v1'],
            'holder' => ['actor_id' => 'agentmail-transport', 'office' => 'la-cortine', 'seat' => 'la-cortine.deterministic-boundary-executor', 'runtime_principal_id' => 'agentmail-email-send-command', 'competent_service' => 'la-cortine.deterministic-boundary-executor'],
            'replay_fingerprint' => hash('sha256', 'replay'),
            'execution_identity' => ['execution_id' => 'deterministic-execution-0123456789abcdef0123', 'single_use' => true, 'winner_scope' => 'authorization:outbound-email-authorization-0123456789abcdef0123', 'lock_order' => ['authorization', 'execution-claim']],
            'credential_capability' => ['capability_id' => 'credential-capability.test', 'credential_reference_digest' => hash('sha256', 'credential-reference-only'), 'commission_id' => 'commission-test', 'operation' => 'email.send', 'expires_at' => $this->time('+5 minutes')->format(DATE_ATOM), 'max_uses' => 1],
            'provider_safety' => ['strategy' => 'PROVIDER_IDEMPOTENCY_KEY', 'provider_idempotency_key' => 'iron-gate-test-key', 'provider_contract_reference' => 'docs/runtime/agentmail-email-transport.md', 'automatic_replay_permitted' => false, 'unknown_outcome_status' => 'NOT_STARTED'],
            'effect' => ['checkpoint' => 'CLAIMED_PRE_IO', 'external_io_started' => false, 'outcome' => 'NOT_ATTEMPTED', 'effect_started_at' => null, 'resolved_at' => null],
            'claimed_at' => $this->time('-1 minute')->format(DATE_ATOM),
            'expires_at' => $this->time('+5 minutes')->format(DATE_ATOM),
            'sealed' => true,
        ];
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $directory = $this->root.'/'.DeterministicExecutionClaimService::CLAIMS;
        mkdir($directory, 0770, true);
        file_put_contents($directory.'/'.$this->claimId.'.json', json_encode($record, JSON_THROW_ON_ERROR));
    }

    private function time(string $modifier = ''): \DateTimeImmutable
    {
        $time = new \DateTimeImmutable('2035-01-01T00:00:00+00:00');
        return '' === $modifier ? $time : $time->modify($modifier);
    }

    private function payload(): string
    {
        return '{"to":["test@example.test"]}';
    }

    private function invokeOnce(): array
    {
        $journal = (new DeterministicEffectStartJournalService($this->root))->start($this->claimId, $this->time('+1 minute'));
        $capability = new CredentialCapability('credential-capability.test', 'credential-reference-only', 'commission-test', 'email.send', $this->time('+5 minutes'));
        (new DeterministicJournalBoundCredentialBroker($this->root, $this->credentials($capability), new AgentMailIdempotencyHeaderAdapter()))->invoke($journal['journal_id'], $capability, $this->payload(), $this->time('+2 minutes'), static fn (): array => ['observed' => true]);
        $paths = glob($this->root.'/'.DeterministicJournalBoundCredentialBroker::ADMISSIONS.'/*.json') ?: [];
        self::assertCount(1, $paths);
        return json_decode((string) file_get_contents($paths[0]), true, 512, JSON_THROW_ON_ERROR);
    }

    private function credentials(CredentialCapability $expected): CredentialBroker
    {
        return new class($expected) implements CredentialBroker {
            private int $uses = 0;

            public function __construct(private CredentialCapability $expected)
            {
            }

            public function issue(string $credentialRef, string $commissionId, string $operation, \DateTimeImmutable $expiresAt, int $maxUses = 1): CredentialCapability
            {
                throw new \LogicException('The Batch 8 broker must not issue capabilities.');
            }

            public function consume(CredentialCapability $capability, callable $providerOperation): mixed
            {
                if ($capability !== $this->expected || $this->uses > 0) throw new \RuntimeException('TEST_CAPABILITY_INVALID');
                ++$this->uses;
                return $providerOperation('test-secret-material');
            }
        };
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
