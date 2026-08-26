<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\BearerJsonPostTransport;
use App\Imperium\Runtime\LaCortine\DeterministicBoundaryExecutor;
use App\Imperium\Runtime\LaCortine\DeterministicTransport;
use App\Imperium\Runtime\LaCortine\EnvironmentCredentialBroker;
use App\Imperium\Runtime\LaCortine\CredentialCapability;
use App\Imperium\Runtime\LaCortine\IronGate;
use App\Imperium\Runtime\LaCortine\Lazaretto;
use App\Imperium\Runtime\LaCortine\OutboundExecutionMode;
use App\Imperium\Runtime\LaCortine\OutboundRequest;
use App\Imperium\Runtime\LaCortine\TransportResult;
use PHPUnit\Framework\TestCase;

final class DeterministicBoundaryExecutorTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_ENV['IMPERIUM_TEST_TOKEN'], $_SERVER['IMPERIUM_TEST_TOKEN']);
    }

    public function testExactDeterministicRequestCrossesIronGateUsesCredentialAndReturnsThroughLazaretto(): void
    {
        $_ENV['IMPERIUM_TEST_TOKEN'] = 'test-secret-never-admitted';
        $now = new \DateTimeImmutable();
        $payload = '{"to":"client@example.test","artifact_sha256":"abc"}';
        $broker = new EnvironmentCredentialBroker();
        $credential = $broker->issue(
            'env:IMPERIUM_TEST_TOKEN',
            'commission-1',
            'http.post.json',
            $now->modify('+5 minutes'),
        );
        $request = $this->request($payload, $credential->capabilityId, $now);

        $transport = new class implements DeterministicTransport {
            public mixed $authentication = null;

            public function supports(string $operation): bool
            {
                return 'http.post.json' === $operation;
            }

            public function execute(string $operation, string $destination, string $payload, mixed $authentication): TransportResult
            {
                $this->authentication = $authentication;

                return new TransportResult(
                    '{"status":"sent","provider_id":"msg-1"}',
                    [$destination, 'provider-message:msg-1'],
                    new \DateTimeImmutable(),
                );
            }
        };

        $artifact = (new DeterministicBoundaryExecutor(new IronGate(), $broker, new Lazaretto()))
            ->execute($request, $payload, $credential, $transport, $now);

        self::assertSame('test-secret-never-admitted', $transport->authentication);
        self::assertSame('{"status":"sent","provider_id":"msg-1"}', $artifact->content);
        self::assertSame($credential->capabilityId, $artifact->provenance['capability_ids'][0]);
        self::assertNull($artifact->provenance['sortie_id']);
        self::assertStringNotContainsString('test-secret-never-admitted', json_encode($artifact->provenance, JSON_THROW_ON_ERROR));
    }

    public function testAuthorizedPayloadDigestIsEnforcedBeforeCredentialUse(): void
    {
        $_ENV['IMPERIUM_TEST_TOKEN'] = 'test-secret';
        $now = new \DateTimeImmutable();
        $broker = new EnvironmentCredentialBroker();
        $credential = $broker->issue('env:IMPERIUM_TEST_TOKEN', 'commission-1', 'http.post.json', $now->modify('+5 minutes'));
        $request = $this->request('authorized', $credential->capabilityId, $now);
        $transport = new class implements DeterministicTransport {
            public function supports(string $operation): bool { return true; }
            public function execute(string $operation, string $destination, string $payload, mixed $authentication): TransportResult
            {
                throw new \LogicException('Transport must not be reached.');
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DETERMINISTIC_PAYLOAD_MISMATCH');
        (new DeterministicBoundaryExecutor(new IronGate(), $broker, new Lazaretto()))
            ->execute($request, 'tampered', $credential, $transport, $now);
    }

    public function testOneUseCredentialCannotReplayExternalExecution(): void
    {
        $_ENV['IMPERIUM_TEST_TOKEN'] = 'test-secret';
        $now = new \DateTimeImmutable();
        $payload = '{}';
        $broker = new EnvironmentCredentialBroker();
        $credential = $broker->issue('env:IMPERIUM_TEST_TOKEN', 'commission-1', 'http.post.json', $now->modify('+5 minutes'));
        $request = $this->request($payload, $credential->capabilityId, $now);
        $transport = new class implements DeterministicTransport {
            public function supports(string $operation): bool { return true; }
            public function execute(string $operation, string $destination, string $payload, mixed $authentication): TransportResult
            {
                return new TransportResult('{}', [$destination], new \DateTimeImmutable());
            }
        };
        $executor = new DeterministicBoundaryExecutor(new IronGate(), $broker, new Lazaretto());
        $executor->execute($request, $payload, $credential, $transport, $now);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CREDENTIAL_CAPABILITY_CONSUMED');
        $executor->execute($request, $payload, $credential, $transport, $now);
    }

    public function testForgedCredentialCapabilityCannotResolveASecret(): void
    {
        $_ENV['IMPERIUM_TEST_TOKEN'] = 'test-secret-must-not-resolve';
        $broker = new EnvironmentCredentialBroker();
        $forged = new CredentialCapability(
            'credential-capability.forged',
            'env:IMPERIUM_TEST_TOKEN',
            'commission-1',
            'http.post.json',
            new \DateTimeImmutable('+5 minutes'),
        );
        $callbackReached = false;

        try {
            $broker->consume($forged, static function () use (&$callbackReached): never {
                $callbackReached = true;
                throw new \LogicException('Credential callback must not be reached.');
            });
            self::fail('Expected an unissued capability to be rejected.');
        } catch (\RuntimeException $exception) {
            self::assertStringStartsWith('CREDENTIAL_CAPABILITY_UNISSUED', $exception->getMessage());
        }

        self::assertFalse($callbackReached);
    }

    public function testCapabilityIssuedByAnotherBrokerCannotResolveASecret(): void
    {
        $_ENV['IMPERIUM_TEST_TOKEN'] = 'test-secret-must-not-resolve';
        $issuer = new EnvironmentCredentialBroker();
        $consumer = new EnvironmentCredentialBroker();
        $capability = $issuer->issue(
            'env:IMPERIUM_TEST_TOKEN',
            'commission-1',
            'http.post.json',
            new \DateTimeImmutable('+5 minutes'),
        );

        $this->expectExceptionMessage('CREDENTIAL_CAPABILITY_UNISSUED');
        $consumer->consume($capability, static fn (): never => throw new \LogicException('Callback must not run.'));
    }

    public function testExpiredIssuedCapabilityCannotResolveASecret(): void
    {
        $_ENV['IMPERIUM_TEST_TOKEN'] = 'test-secret-must-not-resolve';
        $broker = new EnvironmentCredentialBroker();
        $capability = $broker->issue(
            'env:IMPERIUM_TEST_TOKEN',
            'commission-1',
            'http.post.json',
            new \DateTimeImmutable('-1 second'),
        );

        $this->expectExceptionMessage('CREDENTIAL_CAPABILITY_EXPIRED');
        $broker->consume($capability, static fn (): never => throw new \LogicException('Callback must not run.'));
    }

    public function testBearerJsonTransportRejectsNonHttpsBeforeNetworkAccess(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HTTP_DESTINATION_REJECTED');
        (new BearerJsonPostTransport())->execute('http.post.json', 'http://example.test/send', '{}', 'secret');
    }

    private function request(string $payload, string $capabilityId, \DateTimeImmutable $now): OutboundRequest
    {
        return new OutboundRequest(
            'request-1',
            'authorization-1',
            str_repeat('a', 64),
            'commission-1',
            'http.post.json',
            'Send exact approved payload to exact external service',
            OutboundExecutionMode::Deterministic,
            ['https://email-service.example.test/send'],
            ['http.post'],
            [$capabilityId],
            hash('sha256', $payload),
            'provider-delivery-receipt/v1',
            $now->modify('+5 minutes'),
        );
    }
}
