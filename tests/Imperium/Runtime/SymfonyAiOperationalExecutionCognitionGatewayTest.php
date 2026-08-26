<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\DeepSeekDelegatePlatformAdapter;
use App\Imperium\Runtime\Clavium\OperationalClaimBoundCredentialBroker;
use App\Imperium\Runtime\Clavium\ProviderInvocationJournalService;
use App\Imperium\Runtime\Clavium\ProviderResponseEnvelopeService;
use App\Imperium\Runtime\Clock;
use App\Imperium\Runtime\LaCortine\CredentialBroker;
use App\Imperium\Runtime\LaCortine\CredentialCapability;
use App\Imperium\Runtime\Mission\SymfonyAiOperationalExecutionCognitionGateway;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Message\MessageBag;

final class SymfonyAiOperationalExecutionCognitionGatewayTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-operational-gateway-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testBrokeredClaimDeliversSecretOnceAndSealsResponse(): void
    {
        [$authorization, $manifestation, $claim] = $this->lineage();
        $result = [
            'disposition' => 'COMPLETED',
            'output' => 'Bounded output.',
            'evidence_claims' => ['Exact input supplied.'],
            'uncertainties' => [],
            'stop_condition_triggered' => false,
            'stop_rationale' => 'No stop condition triggered.',
        ];
        $adapter = new class(json_encode($result, JSON_THROW_ON_ERROR)) implements DeepSeekDelegatePlatformAdapter {
            public array $received = [];
            public function __construct(private string $response) {}
            public function invoke(string $secret, string $runtimeModel, MessageBag $messages, array $configuration, string $idempotencyKey): string
            {
                $this->received = [$secret, $runtimeModel, $configuration, $idempotencyKey];
                return $this->response;
            }
        };

        $actual = $this->gateway($adapter, $this->credentials())->execute($authorization, $manifestation);

        self::assertSame($result, $actual);
        self::assertSame('test-operational-secret', $adapter->received[0]);
        self::assertSame($claim['provider_request']['idempotency_identity'], $adapter->received[3]);
        $journal = $this->read('var/imperium/runtime/provider-invocation-journal/'.$claim['claim_id'].'.json');
        self::assertSame('PROVIDER_RESPONSE_IDENTITY_SEALED_PENDING_RESULT_PROCESSING', $journal['status']);
        $envelope = $this->read('var/imperium/runtime/provider-response-envelopes/'.$claim['claim_id'].'.json');
        self::assertSame(json_encode($result, JSON_THROW_ON_ERROR), $envelope['response']);
        self::assertStringNotContainsString('test-operational-secret', CanonicalJson::encode([$journal, $envelope]));
        self::assertStringNotContainsString('test-operational-secret', $this->persistedJson());
    }

    public function testMissingDurableClaimBlocksCredentialResolution(): void
    {
        $authorization = [
            'authorization_id' => 'bounded-execution-authorization-'.str_repeat('a', 20),
            'manifestation' => ['manifestation_id' => 'manifestation-test'],
            'record_digest' => str_repeat('b', 64),
        ];
        $credentials = new class implements CredentialBroker {
            public function issue(string $credentialRef, string $commissionId, string $operation, \DateTimeImmutable $expiresAt, int $maxUses = 1): CredentialCapability { throw new \LogicException('Credential issue must not be reached.'); }
            public function consume(CredentialCapability $capability, callable $providerOperation): mixed { throw new \LogicException('Credential consume must not be reached.'); }
        };

        $this->expectExceptionMessage('M210_OPERATIONAL_PROVIDER_CLAIM_UNAVAILABLE');
        $this->gateway($this->unreachableAdapter(), $credentials)->execute($authorization, $authorization['manifestation']);
    }

    #[DataProvider('invalidClaimCases')]
    public function testMalformedMismatchedExpiredConsumedAndSupersededClaimsBlockBeforeCredentialIssue(string $case): void
    {
        [$authorization, $manifestation, $claim] = $this->lineage();
        $path = 'var/imperium/runtime/operational-cognition-invocation-claims/'.$claim['claim_id'].'.json';
        if ('malformed' === $case) {
            file_put_contents($this->root.'/'.$path, '{not-json');
        } elseif ('mismatched' === $case) {
            $claim['provider'] = 'substituted-provider';
            unset($claim['record_digest']);
            $this->persist($path, $claim);
        } elseif ('expired' === $case) {
            $claim['lease_consumption']['expires_at'] = '2026-08-26T13:00:00+00:00';
            unset($claim['record_digest']);
            $this->persist($path, $claim);
        } elseif ('consumed' === $case) {
            $claim['cognition_authority_consumption']['consumed'] = false;
            unset($claim['record_digest']);
            $this->persist($path, $claim);
        } else {
            $claim['claim_id'] = 'operational-cognition-invocation-claim-'.str_repeat('9', 20);
            $claim['provider_request']['idempotency_identity'] = 'imperium-'.$claim['claim_id'];
            unset($claim['record_digest']);
            $this->persist('var/imperium/runtime/operational-cognition-invocation-claims/'.$claim['claim_id'].'.json', $claim);
        }
        $credentials = $this->trackingCredentials();

        try {
            $this->gateway($this->unreachableAdapter(), $credentials)->execute($authorization, $manifestation);
            self::fail('Expected invalid claim to fail stopped.');
        } catch (\RuntimeException $exception) {
            self::assertSame('M210_OPERATIONAL_PROVIDER_CLAIM_UNAVAILABLE', $exception->getMessage());
        }
        self::assertSame(0, $credentials->issued);
        self::assertSame(0, $credentials->consumed);
    }

    public static function invalidClaimCases(): array
    {
        return [['malformed'], ['mismatched'], ['expired'], ['consumed'], ['superseded']];
    }

    public function testExactReplayIsRejectedBeforeSecondCredentialResolution(): void
    {
        [$authorization, $manifestation] = $this->lineage();
        $credentials = $this->trackingCredentials();
        $adapter = new class implements DeepSeekDelegatePlatformAdapter {
            public int $invocations = 0;
            public function invoke(string $secret, string $runtimeModel, MessageBag $messages, array $configuration, string $idempotencyKey): string
            {
                ++$this->invocations;
                return '{"disposition":"COMPLETED","output":"Bounded.","evidence_claims":[],"uncertainties":[],"stop_condition_triggered":false,"stop_rationale":"Complete."}';
            }
        };
        $gateway = $this->gateway($adapter, $credentials);
        $gateway->execute($authorization, $manifestation);

        try {
            $gateway->execute($authorization, $manifestation);
            self::fail('Expected exact replay to fail stopped.');
        } catch (\RuntimeException $exception) {
            self::assertSame('M214_OPERATIONAL_PROVIDER_REPLAY_PROHIBITED', $exception->getMessage());
        }
        self::assertSame(1, $credentials->issued);
        self::assertSame(1, $credentials->consumed);
        self::assertSame(1, $adapter->invocations);
    }

    public function testCredentialFailureAfterReservationIsSealedPreIoWithoutDiagnostic(): void
    {
        [$authorization, $manifestation, $claim] = $this->lineage();
        $credentials = new class implements CredentialBroker {
            public function issue(string $credentialRef, string $commissionId, string $operation, \DateTimeImmutable $expiresAt, int $maxUses = 1): CredentialCapability { return new CredentialCapability('capability', $credentialRef, $commissionId, $operation, $expiresAt, $maxUses); }
            public function consume(CredentialCapability $capability, callable $providerOperation): mixed { throw new \RuntimeException('secret-bearing credential diagnostic'); }
        };

        try {
            $this->gateway($this->unreachableAdapter(), $credentials)->execute($authorization, $manifestation);
            self::fail('Expected pre-I/O failure.');
        } catch (\RuntimeException $exception) {
            self::assertSame('M213_OPERATIONAL_PROVIDER_PRE_IO_FAILURE', $exception->getMessage());
            self::assertNull($exception->getPrevious());
        }
        $journal = $this->read('var/imperium/runtime/provider-invocation-journal/'.$claim['claim_id'].'.json');
        self::assertSame('INVOCATION_FAILED_PRE_IO_REPLAY_PROHIBITED', $journal['status']);
        self::assertFalse($journal['external_io_started']);
        self::assertStringNotContainsString('secret-bearing', CanonicalJson::encode($journal));
    }

    public function testInterruptedPreIoReservationProhibitsRecoveryReplayBeforeCredentialResolution(): void
    {
        [$authorization, $manifestation, $claim] = $this->lineage();
        (new ProviderInvocationJournalService($this->root))->reserveOperational($claim, $this->clock()->now());
        $credentials = $this->trackingCredentials();

        try {
            $this->gateway($this->unreachableAdapter(), $credentials)->execute($authorization, $manifestation);
            self::fail('Expected interrupted reservation replay to fail stopped.');
        } catch (\RuntimeException $exception) {
            self::assertSame('M214_OPERATIONAL_PROVIDER_REPLAY_PROHIBITED', $exception->getMessage());
        }
        self::assertSame(0, $credentials->issued);
        self::assertSame(0, $credentials->consumed);
        $journal = $this->read('var/imperium/runtime/provider-invocation-journal/'.$claim['claim_id'].'.json');
        self::assertSame('INVOCATION_RESERVED_PRE_IO', $journal['status']);
        self::assertFalse($journal['automatic_replay_permitted']);
    }

    public function testProviderFailureIsUnknownAndReplayProhibited(): void
    {
        [$authorization, $manifestation, $claim] = $this->lineage();
        $adapter = new class implements DeepSeekDelegatePlatformAdapter {
            public function invoke(string $secret, string $runtimeModel, MessageBag $messages, array $configuration, string $idempotencyKey): string { throw new \RuntimeException('secret-bearing provider diagnostic'); }
        };

        try {
            $this->gateway($adapter, $this->credentials())->execute($authorization, $manifestation);
            self::fail('Expected unknown provider outcome.');
        } catch (\RuntimeException $exception) {
            self::assertSame('M212_OPERATIONAL_PROVIDER_OUTCOME_UNKNOWN', $exception->getMessage());
            self::assertNull($exception->getPrevious());
        }

        $journal = $this->read('var/imperium/runtime/provider-invocation-journal/'.$claim['claim_id'].'.json');
        self::assertSame('PROVIDER_OUTCOME_UNKNOWN_REPLAY_PROHIBITED', $journal['status']);
        self::assertFalse($journal['automatic_replay_permitted']);
        self::assertStringNotContainsString('secret-bearing', CanonicalJson::encode($journal));
    }

    public function testOperationalAgentDefinitionAndDirectAgentInjectionAreAbsent(): void
    {
        $configuration = (string) file_get_contents(dirname(__DIR__, 3).'/config/packages/ai.yaml');
        $gateway = (string) file_get_contents(dirname(__DIR__, 3).'/src/Imperium/Runtime/Mission/SymfonyAiOperationalExecutionCognitionGateway.php');
        self::assertStringNotContainsString('operational_manifestation_execution', $configuration);
        self::assertStringNotContainsString('AgentInterface', $gateway);
        self::assertStringNotContainsString('ai.agent.', $gateway);
    }

    private function gateway(DeepSeekDelegatePlatformAdapter $adapter, CredentialBroker $credentials): SymfonyAiOperationalExecutionCognitionGateway
    {
        return new SymfonyAiOperationalExecutionCognitionGateway(
            new OperationalClaimBoundCredentialBroker($this->root, $credentials),
            new ProviderInvocationJournalService($this->root),
            new ProviderResponseEnvelopeService($this->root),
            $adapter,
            $this->clock(),
        );
    }

    private function lineage(): array
    {
        $authorizationId = 'bounded-execution-authorization-'.str_repeat('a', 20);
        $manifestation = ['manifestation_id' => 'manifestation-test'];
        $authorization = ['authorization_id' => $authorizationId, 'manifestation' => $manifestation, 'record_digest' => str_repeat('b', 64)];
        $target = ['seat' => 'seat-test', 'manifestation_id' => 'manifestation-test', 'binding_id' => 'binding', 'binding_digest' => str_repeat('c', 64), 'custody_id' => 'custody', 'custody_digest' => str_repeat('d', 64)];
        $requestId = 'operational-cognition-request-'.str_repeat('e', 20);
        $request = [
            'schema' => 'imperium.curia-operational-cognition-request/v1',
            'request_id' => $requestId,
            'source_bounded_execution_authorization' => ['id' => $authorizationId, 'digest' => $authorization['record_digest']],
            'target' => $target,
            'sealed' => true,
        ];
        $request = $this->persist('var/imperium/offices/curia/operational-cognition-requests/'.$requestId.'.json', $request);
        $claimId = 'operational-cognition-invocation-claim-'.str_repeat('f', 20);
        $claim = [
            'schema' => 'imperium.clavium-operational-cognition-invocation-claim/v1',
            'claim_id' => $claimId,
            'source_cognition_request' => ['id' => $requestId, 'digest' => $request['record_digest']],
            'target' => $target,
            'provider' => 'deepseek',
            'model' => 'deepseek-v4-flash',
            'model_configuration' => ['temperature' => 0.2],
            'lease_consumption' => ['lease_id' => 'lease', 'consumed' => true, 'consumed_at' => '2026-08-26T12:59:00+00:00', 'expires_at' => '2026-08-26T14:00:00+00:00', 'continuing_authority' => false],
            'cognition_authority_consumption' => ['authority_id' => 'authority', 'consumed' => true, 'consumed_at' => '2026-08-26T12:59:00+00:00', 'continuing_authority' => false],
            'provider_request' => ['idempotency_identity' => 'imperium-'.$claimId, 'external_io_started' => false, 'provider_response_identity' => null],
            'recovery' => ['automatic_replay_permitted' => false, 'unknown_outcome_requires_governed_resolution' => true],
            'status' => 'OPERATIONAL_INVOCATION_CLAIMED_DURABLE_PRE_IO',
            'credential_resolved' => false,
            'credential_material_present' => false,
            'network_access_performed' => false,
            'execution_continuation_authority' => false,
            'sealed' => true,
        ];
        $claim = $this->persist('var/imperium/runtime/operational-cognition-invocation-claims/'.$claimId.'.json', $claim);

        return [$authorization, $manifestation, $claim];
    }

    private function credentials(): CredentialBroker
    {
        return new class implements CredentialBroker {
            public function issue(string $credentialRef, string $commissionId, string $operation, \DateTimeImmutable $expiresAt, int $maxUses = 1): CredentialCapability { return new CredentialCapability('capability', $credentialRef, $commissionId, $operation, $expiresAt, $maxUses); }
            public function consume(CredentialCapability $capability, callable $providerOperation): mixed { return $providerOperation('test-operational-secret'); }
        };
    }

    private function trackingCredentials(): CredentialBroker
    {
        return new class implements CredentialBroker {
            public int $issued = 0;
            public int $consumed = 0;
            public function issue(string $credentialRef, string $commissionId, string $operation, \DateTimeImmutable $expiresAt, int $maxUses = 1): CredentialCapability
            {
                ++$this->issued;
                return new CredentialCapability('capability-'.$this->issued, $credentialRef, $commissionId, $operation, $expiresAt, $maxUses);
            }
            public function consume(CredentialCapability $capability, callable $providerOperation): mixed
            {
                ++$this->consumed;
                return $providerOperation('test-operational-secret');
            }
        };
    }

    private function unreachableAdapter(): DeepSeekDelegatePlatformAdapter
    {
        return new class implements DeepSeekDelegatePlatformAdapter {
            public function invoke(string $secret, string $runtimeModel, MessageBag $messages, array $configuration, string $idempotencyKey): string { throw new \LogicException('Adapter must not be reached.'); }
        };
    }

    private function clock(): Clock
    {
        return new class implements Clock { public function now(): \DateTimeImmutable { return new \DateTimeImmutable('2026-08-26T13:00:00+00:00'); } };
    }

    private function persist(string $relative, array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->root.'/'.$relative;
        if (!is_dir(dirname($path))) { mkdir(dirname($path), 0770, true); }
        file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
        return $record;
    }

    private function read(string $relative): array
    {
        return json_decode((string) file_get_contents($this->root.'/'.$relative), true, 512, JSON_THROW_ON_ERROR);
    }

    private function persistedJson(): string
    {
        $serialized = '';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root));
        foreach ($iterator as $file) {
            if ($file->isFile() && 'json' === $file->getExtension()) {
                $serialized .= (string) file_get_contents($file->getPathname());
            }
        }

        return $serialized;
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) { return; }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->remove($child) : unlink($child);
        }
        rmdir($path);
    }
}
