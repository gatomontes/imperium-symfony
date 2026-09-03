<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\NativeConsumer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectAtomicAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectContinuationCapability;
use App\Imperium\Runtime\ProviderTransition\NativeEffectContinuationCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectCredentialCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectDoubleExecutionService;

require_once __DIR__.'/CanonicalNativeEffectCorridorActivationBatch4Test.php';

final class CanonicalNativeEffectContinuationExclusivityRemediationBatch3Test extends CanonicalNativeEffectCorridorActivationBatch4Test
{
    public function testFreshIssuerAndCopiedCapabilityCannotBeginFirstCallback(): void
    {
        [$outcome, , $at, $payload, $key] = $this->correctedAdmission();
        $copy = $this->copy($outcome->continuation);
        $fresh = new NativeEffectContinuationCapabilityIssuer();
        $calls = 0;
        $this->fails('CNE400_EFFECT_CONTINUATION_INVALID', function () use ($fresh, $outcome, $copy, $payload, $key, $at, &$calls): void {
            (new NativeEffectDoubleExecutionService($this->state, $fresh))->execute(
                $outcome['admission_id'], $copy, $payload, $key, $at,
                static function () use (&$calls): array { ++$calls; return []; },
            );
        });
        self::assertSame(0, $calls);
        self::assertSame([], glob($this->root.'/'.NativeEffectDoubleExecutionService::CALLBACK_STARTS.'/*.json') ?: []);
    }

    public function testTamperedCallerAuthorityHasNoContinuationOrReceiptInputChannel(): void
    {
        [$outcome, $continuations, $at, $payload, $key, $authority] = $this->correctedAdmission();
        $authority['expected_return_contract'] = 'attacker.old-digest-substitution/v1';
        $authority['provider']['provider_id'] = 'attacker-provider';
        $authority['destination'] = 'https://attacker.invalid';

        $receipt = (new NativeEffectDoubleExecutionService($this->state, $continuations))->execute(
            $outcome['admission_id'], $outcome->continuation, $payload, $key, $at,
            static fn (): array => ['http_status' => 202, 'headers' => [], 'body' => '{"message_id":"m1","thread_id":"t1"}', 'observed_at' => $at, 'received_at' => $at],
        );

        self::assertSame('agentmail.message/v1', $receipt['lazaretto_admission']['expected_return_contract']);
        self::assertSame($outcome['effect_authority'], $receipt['effect_authority']);
        self::assertNotSame($authority['provider']['provider_id'], $outcome['receipt_input']['provider']['provider_id']);
        self::assertNotSame($authority['destination'], $outcome['receipt_input']['provider_request']['destination']);
    }

    public function testWrongPayloadOrKeyRefusesBeforeCapabilityConsumption(): void
    {
        [$outcome, $continuations, $at, $payload, $key] = $this->correctedAdmission();
        $service = new NativeEffectDoubleExecutionService($this->state, $continuations);
        $this->fails('CNE400_EFFECT_CONTINUATION_INVALID', fn () => $service->execute($outcome['admission_id'], $outcome->continuation, $payload.'x', $key, $at, static fn (): array => []));
        self::assertTrue($continuations->recognizes($outcome->continuation));
        $this->fails('CNE400_EFFECT_CONTINUATION_INVALID', fn () => $service->execute($outcome['admission_id'], $outcome->continuation, $payload, $key.'x', $at, static fn (): array => []));
        self::assertTrue($continuations->recognizes($outcome->continuation));
        self::assertSame([], glob($this->root.'/'.NativeEffectDoubleExecutionService::CALLBACK_STARTS.'/*.json') ?: []);
    }

    public function testSealedResponseForwardCompletesAfterExpiryWithoutCapabilityOrCallback(): void
    {
        [$outcome, $continuations, $at, $payload, $key] = $this->correctedAdmission();
        $calls = 0;
        $first = new NativeEffectDoubleExecutionService($this->state, $continuations, static function (string $cut): void {
            if ('response.sealed' === $cut) { throw new \RuntimeException('synthetic process loss'); }
        });
        $this->fails('UNKNOWN_REPLAY_PROHIBITED', function () use ($first, $outcome, $payload, $key, $at, &$calls): void {
            $first->execute($outcome['admission_id'], $outcome->continuation, $payload, $key, $at, static function () use (&$calls, $at): array {
                ++$calls;
                return ['http_status' => 202, 'headers' => [], 'body' => '{"message_id":"m2","thread_id":"t2"}', 'observed_at' => $at, 'received_at' => $at];
            });
        });
        self::assertSame(1, $calls);

        $fresh = new NativeEffectContinuationCapabilityIssuer();
        $receipt = (new NativeEffectDoubleExecutionService($this->state, $fresh))->execute(
            $outcome['admission_id'], $this->copy($outcome->continuation), $payload, $key, $outcome['expires_at'] + 100,
            static function () use (&$calls): array { ++$calls; return []; },
        );
        self::assertSame('ACCEPTED', $receipt['provider_outcome']['status']);
        self::assertSame(1, $calls);
        self::assertFalse($receipt['recovery']['provider_reinvoked']);
        self::assertGreaterThan($outcome['expires_at'], $receipt['bound_at']);
    }

    public function testExecuteApiAndReceiptBindingContainNoCallerAuthorityArray(): void
    {
        $method = new \ReflectionMethod(NativeEffectDoubleExecutionService::class, 'execute');
        $parameters = $method->getParameters();
        self::assertSame('admissionId', $parameters[0]->getName());
        self::assertSame('continuation', $parameters[1]->getName());
        self::assertSame(NativeEffectContinuationCapability::class, (string) $parameters[1]->getType());
        self::assertNotContains('authority', array_map(static fn (\ReflectionParameter $parameter): string => $parameter->getName(), $parameters));

        $source = (string) file_get_contents(dirname(__DIR__, 3).'/src/Imperium/Runtime/ProviderTransition/NativeEffectDoubleExecutionService.php');
        foreach (['NativeState::ref($authority', '$authority[\'expected_return_contract\']', 'array $authority'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
        foreach (['receipt_input', 'expected_return_contract', 'provider_id', 'authority_consumption_id'] as $required) {
            self::assertStringContainsString($required, $source);
        }
    }

    private function correctedAdmission(): array
    {
        [$transitionAuthority, $at] = $this->readyTransition();
        $native = (new NativeConsumer($this->state, static fn () => $at))->execute($transitionAuthority);
        $authority = $this->effectAuthority($native['root'], $at);
        $credentials = new NativeEffectCredentialCapabilityIssuer();
        $continuations = new NativeEffectContinuationCapabilityIssuer();
        $credential = $credentials->issue($authority, $authority['execution_boundary']['id'], $at);
        $outcome = (new NativeEffectAtomicAdmissionService($this->state, $credentials, $continuations))->admit($authority, $credential, $at);
        return [$outcome, $continuations, $at, '{"to":["disposable@example.test"]}', 'disposable-idempotency-key', $authority];
    }

    private function copy(NativeEffectContinuationCapability $capability): NativeEffectContinuationCapability
    {
        return new NativeEffectContinuationCapability(
            $capability->capabilityId,
            $capability->admissionId,
            $capability->admissionDigest,
            $capability->semanticEffectTupleId,
            $capability->authorityConsumptionId,
            $capability->processBoundaryId,
            $capability->expiresAt,
        );
    }
}
