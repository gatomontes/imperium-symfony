<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\DelegateSymfonyPlatformAdapter;
use App\Imperium\Runtime\Citadel\SymfonyAiBrokeredDelegateProviderInvoker;
use App\Imperium\Runtime\Clavium\ProviderInvocationJournalService;
use App\Imperium\Runtime\Clavium\ProviderResponseEnvelopeService;
use App\Imperium\Runtime\Clock;
use App\Imperium\Runtime\LaCortine\CredentialBroker;
use App\Imperium\Runtime\LaCortine\CredentialCapability;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

final class SymfonyAiBrokeredDelegateProviderInvokerTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-brokered-invoker-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testSecretReachesAdapterOnlyInsideBrokerCallbackAndResponseIdentityIsSealed(): void
    {
        $claim = $this->claim();
        $broker = $this->successfulBroker();
        $adapter = new class implements DelegateSymfonyPlatformAdapter {
            public array $received = [];

            public function invoke(string $secret, string $runtimeModel, MessageBag $messages, array $configuration, string $idempotencyKey): string
            {
                $this->received = [$secret, $runtimeModel, $configuration, $idempotencyKey];

                return '{"disposition":"COMPLETED"}';
            }
        };
        $invoker = new SymfonyAiBrokeredDelegateProviderInvoker(
            $broker,
            new ProviderInvocationJournalService($this->root),
            new ProviderResponseEnvelopeService($this->root),
            $adapter,
            $this->clock(),
        );

        $response = $invoker->invoke(
            $claim,
            'deepseek-v4-flash',
            new MessageBag(Message::ofUser('bounded')),
            ['temperature' => 0.2],
        );

        self::assertSame('{"disposition":"COMPLETED"}', $response);
        self::assertSame('test-secret-never-persisted', $adapter->received[0]);
        self::assertSame($claim['provider_request']['idempotency_key'], $adapter->received[3]);
        $journal = $this->journal($claim['claim_id']);
        self::assertSame('PROVIDER_RESPONSE_IDENTITY_SEALED_PENDING_RESULT_PROCESSING', $journal['status']);
        self::assertStringNotContainsString('test-secret-never-persisted', CanonicalJson::encode($journal));
        self::assertStringNotContainsString($response, CanonicalJson::encode($journal));
        $envelope = $this->envelope($claim['claim_id']);
        self::assertSame($response, $envelope['response']);
        self::assertSame($journal['provider_response_identity'], $envelope['provider_response_identity']);
        self::assertFalse($envelope['automatic_provider_replay_permitted']);
        self::assertStringNotContainsString('test-secret-never-persisted', CanonicalJson::encode($envelope));
    }

    public function testCredentialFailureBeforeCallbackProducesExplicitPreIoFailure(): void
    {
        $claim = $this->claim();
        $broker = new class implements CredentialBroker {
            public function issue(string $credentialRef, string $commissionId, string $operation, \DateTimeImmutable $expiresAt, int $maxUses = 1): CredentialCapability
            {
                return new CredentialCapability('capability', $credentialRef, $commissionId, $operation, $expiresAt, $maxUses);
            }
            public function consume(CredentialCapability $capability, callable $providerOperation): mixed
            {
                throw new \RuntimeException('secret-bearing infrastructure diagnostic must be suppressed');
            }
        };
        $adapter = $this->unreachableAdapter();
        $invoker = new SymfonyAiBrokeredDelegateProviderInvoker($broker, new ProviderInvocationJournalService($this->root), new ProviderResponseEnvelopeService($this->root), $adapter, $this->clock());

        try {
            $invoker->invoke($claim, 'deepseek-v4-flash', new MessageBag(Message::ofUser('bounded')), []);
            self::fail('Expected pre-I/O failure.');
        } catch (\RuntimeException $exception) {
            self::assertSame('CT323_DELEGATE_PROVIDER_PRE_IO_FAILURE', $exception->getMessage());
            self::assertStringNotContainsString('secret-bearing', $exception->getMessage());
        }

        $journal = $this->journal($claim['claim_id']);
        self::assertSame('INVOCATION_FAILED_PRE_IO_REPLAY_PROHIBITED', $journal['status']);
        self::assertFalse($journal['external_io_started']);
    }

    public function testAdapterFailureAfterStartBecomesUnknownAndDiagnosticIsSuppressed(): void
    {
        $claim = $this->claim();
        $adapter = new class implements DelegateSymfonyPlatformAdapter {
            public function invoke(string $secret, string $runtimeModel, MessageBag $messages, array $configuration, string $idempotencyKey): string
            {
                throw new \RuntimeException('provider diagnostic containing test-secret-never-persisted');
            }
        };
        $invoker = new SymfonyAiBrokeredDelegateProviderInvoker(
            $this->successfulBroker(),
            new ProviderInvocationJournalService($this->root),
            new ProviderResponseEnvelopeService($this->root),
            $adapter,
            $this->clock(),
        );

        try {
            $invoker->invoke($claim, 'deepseek-v4-flash', new MessageBag(Message::ofUser('bounded')), []);
            self::fail('Expected unknown provider outcome.');
        } catch (\RuntimeException $exception) {
            self::assertSame('CT322_DELEGATE_PROVIDER_OUTCOME_UNKNOWN', $exception->getMessage());
            self::assertNull($exception->getPrevious());
        }

        $journal = $this->journal($claim['claim_id']);
        self::assertSame('PROVIDER_OUTCOME_UNKNOWN_REPLAY_PROHIBITED', $journal['status']);
        self::assertTrue($journal['external_io_started']);
        self::assertStringNotContainsString('test-secret-never-persisted', CanonicalJson::encode($journal));
    }

    public function testDirectInvokerRejectsUnknownConfigurationBeforeCredentialResolution(): void
    {
        $broker = new class implements CredentialBroker {
            public function issue(string $credentialRef, string $commissionId, string $operation, \DateTimeImmutable $expiresAt, int $maxUses = 1): CredentialCapability
            {
                throw new \LogicException('Credential resolution must not be reached.');
            }
            public function consume(CredentialCapability $capability, callable $providerOperation): mixed
            {
                throw new \LogicException('Credential consumption must not be reached.');
            }
        };
        $invoker = new SymfonyAiBrokeredDelegateProviderInvoker($broker, new ProviderInvocationJournalService($this->root), new ProviderResponseEnvelopeService($this->root), $this->unreachableAdapter(), $this->clock());

        $this->expectExceptionMessage('CT312_DELEGATE_MODEL_CONFIGURATION_INVALID');
        $invoker->invoke($this->claim(), 'deepseek-v4-flash', new MessageBag(Message::ofUser('bounded')), ['tools' => true]);
    }

    private function successfulBroker(): CredentialBroker
    {
        return new class implements CredentialBroker {
            public function issue(string $credentialRef, string $commissionId, string $operation, \DateTimeImmutable $expiresAt, int $maxUses = 1): CredentialCapability
            {
                return new CredentialCapability('capability', $credentialRef, $commissionId, $operation, $expiresAt, $maxUses);
            }
            public function consume(CredentialCapability $capability, callable $providerOperation): mixed
            {
                return $providerOperation('test-secret-never-persisted');
            }
        };
    }

    private function unreachableAdapter(): DelegateSymfonyPlatformAdapter
    {
        return new class implements DelegateSymfonyPlatformAdapter {
            public function invoke(string $secret, string $runtimeModel, MessageBag $messages, array $configuration, string $idempotencyKey): string
            {
                throw new \LogicException('Adapter must not be reached.');
            }
        };
    }

    private function clock(): Clock
    {
        return new class implements Clock {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2026-08-25T13:00:00+00:00');
            }
        };
    }

    private function claim(): array
    {
        $id = 'provider-invocation-'.str_repeat('a', 20);
        $record = [
            'schema' => 'imperium.clavium-provider-invocation-claim/v1',
            'claim_id' => $id,
            'claim_fingerprint' => str_repeat('b', 64),
            'instance_id' => 'imperium-test',
            'source_activation' => ['id' => 'activation', 'digest' => str_repeat('c', 64)],
            'target' => ['commission_id' => 'commission'],
            'model' => [
                'runtime_binding' => ['provider' => 'deepseek', 'platform_service' => 'ai.platform.generic.deepseek', 'runtime_model' => 'deepseek-v4-flash'],
                'configuration' => ['temperature' => 0.2],
            ],
            'lease_consumption' => ['lease_id' => 'lease', 'consumed' => true, 'consumed_at' => '2026-08-25T12:59:00+00:00', 'expires_at' => '2026-08-25T14:00:00+00:00', 'continuing_authority' => false],
            'turn_authority_consumption' => ['authority_id' => 'authority', 'consumed' => true, 'consumed_at' => '2026-08-25T12:59:00+00:00', 'continuing_authority' => false],
            'provider_request' => ['idempotency_key' => 'imperium-'.$id, 'external_io_started' => false, 'provider_response_identity' => null],
            'recovery' => ['automatic_replay_permitted' => false, 'unknown_outcome_requires_governed_resolution' => true],
            'claimed_at' => '2026-08-25T12:59:00+00:00',
            'status' => 'INVOCATION_CLAIMED_PENDING_EXTERNAL_IO',
            'provider_invoked' => false,
            'credential_material_present' => false,
            'sealed' => true,
        ];
        $path = $this->root.'/var/imperium/runtime/provider-invocations/'.$id.'.json';
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0770, true);
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");

        return $record;
    }

    private function journal(string $claimId): array
    {
        return json_decode((string) file_get_contents($this->root.'/var/imperium/runtime/provider-invocation-journal/'.$claimId.'.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    private function envelope(string $claimId): array
    {
        return json_decode((string) file_get_contents($this->root.'/var/imperium/runtime/provider-response-envelopes/'.$claimId.'.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->remove($child) : unlink($child);
        }
        rmdir($path);
    }
}
