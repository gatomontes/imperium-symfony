<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\NativeConsumer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectAtomicAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectContinuationCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectCredentialCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectDoubleExecutionService;

require_once __DIR__.'/CanonicalNativeEffectCorridorActivationBatch3Test.php';

class CanonicalNativeEffectCorridorActivationBatch4Test extends CanonicalNativeEffectCorridorActivationBatch3Test
{
    public function testAcceptedDoubleBindsRawResponseReceiptAndReplaysWithoutCallback(): void
    {
        [$admission, $continuations, $at, $payload, $key] = $this->admitted();
        $calls = 0;
        $service = new NativeEffectDoubleExecutionService($this->state, $continuations);
        $receipt = $service->execute($admission['admission_id'], $admission->continuation, $payload, $key, $at, function (array $request) use (&$calls, $at): array {
            ++$calls;
            self::assertTrue($request['provider_double_only']);
            self::assertFalse($request['authentication_present']);
            return ['http_status' => 202, 'headers' => ['x-double' => 'yes'], 'body' => '{"message_id":"msg-1","thread_id":"thr-1"}', 'observed_at' => $at, 'received_at' => $at + 1];
        });
        self::assertSame('ACCEPTED', $receipt['provider_outcome']['status']);
        self::assertTrue($receipt['lazaretto_admission']['admitted']);
        self::assertFalse($receipt['continuing_authority']);
        $proof = $service->reconstruct($receipt['receipt_id']);
        self::assertSame($receipt, $proof['receipt']);
        self::assertSame(1, $calls);
        self::assertTrue($proof['read_only']);
        self::assertFalse($proof['provider_reinvoked']);
        self::assertFalse($proof['credential_resolved']);
    }

    public function testCallbackFailureIsUnknownAndCannotInvokeTwice(): void
    {
        [$admission, $continuations, $at, $payload, $key] = $this->admitted();
        $calls = 0;
        $service = new NativeEffectDoubleExecutionService($this->state, $continuations);
        $this->fails('UNKNOWN_REPLAY_PROHIBITED', function () use ($service, $admission, $payload, $key, $at, &$calls): void {
            $service->execute($admission['admission_id'], $admission->continuation, $payload, $key, $at, function () use (&$calls): never {
                ++$calls; throw new \RuntimeException('synthetic interruption');
            });
        });
        $this->fails('UNKNOWN_REPLAY_PROHIBITED', function () use ($service, $admission, $payload, $key, $at, &$calls): void {
            $service->execute($admission['admission_id'], $admission->continuation, $payload, $key, $at + 1, function () use (&$calls): void { ++$calls; });
        });
        self::assertSame(1, $calls);
    }

    public function testRejectedResponseIsTruthfulAndNotAdmitted(): void
    {
        [$admission, $continuations, $at, $payload, $key] = $this->admitted();
        $receipt = (new NativeEffectDoubleExecutionService($this->state, $continuations))->execute(
            $admission['admission_id'], $admission->continuation, $payload, $key, $at,
            static fn (): array => ['http_status' => 422, 'headers' => [], 'body' => 'rejected', 'observed_at' => $at, 'received_at' => $at],
        );
        self::assertSame('REJECTED', $receipt['provider_outcome']['status']);
        self::assertFalse($receipt['lazaretto_admission']['admitted']);
        self::assertFalse($receipt['recovery']['automatic_replay_permitted']);
    }

    public function testDoubleBoundaryContainsNoCredentialOrNetworkImplementation(): void
    {
        $source = file_get_contents(dirname(__DIR__, 3).'/src/Imperium/Runtime/ProviderTransition/NativeEffectDoubleExecutionService.php');
        foreach (['CredentialBroker', 'EnvironmentCredential', 'getenv(', '$_ENV', '$_SERVER', 'HttpClient', 'AgentMailEmailTransport', 'curl_'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    private function admitted(): array
    {
        [$transitionAuthority, $at] = $this->readyTransition();
        $native = (new NativeConsumer($this->state, static fn () => $at))->execute($transitionAuthority);
        $authority = $this->effectAuthority($native['root'], $at);
        $issuer = new NativeEffectCredentialCapabilityIssuer();
        $continuations = new NativeEffectContinuationCapabilityIssuer();
        $capability = $issuer->issue($authority, $authority['execution_boundary']['id'], $at);
        $admission = (new NativeEffectAtomicAdmissionService($this->state, $issuer, $continuations))->admit($authority, $capability, $at);
        return [$admission, $continuations, $at, '{"to":["disposable@example.test"]}', 'disposable-idempotency-key'];
    }
}
