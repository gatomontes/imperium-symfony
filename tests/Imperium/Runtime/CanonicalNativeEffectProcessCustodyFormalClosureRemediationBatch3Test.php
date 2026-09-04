<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\NativeConsumer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectAtomicAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectContinuationCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectCredentialCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectDoubleExecutionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectForwardRecoveryClaimAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectForwardRecoveryService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityIssuanceService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityResolver;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityV2Contract;

require_once __DIR__.'/CanonicalNativeEffectCorridorActivationBatch4Test.php';

final class CanonicalNativeEffectProcessCustodyFormalClosureRemediationBatch3Test extends CanonicalNativeEffectCorridorActivationBatch4Test
{
    public function testExistingReceiptUsesReconstructAndExecuteCannotReturnIt(): void
    {
        [$outcome, $continuations, $at, $payload, $key] = $this->admittedForBatch3();
        $calls = 0;
        $service = new NativeEffectDoubleExecutionService($this->state, $continuations);
        $receipt = $service->execute($outcome['admission_id'], $outcome->continuation, $payload, $key, $at, static function () use (&$calls, $at): array {
            ++$calls;
            return ['http_status' => 202, 'headers' => [], 'body' => '{"message_id":"m1","thread_id":"t1"}', 'observed_at' => $at, 'received_at' => $at];
        });

        $this->fails('CNE507_FIRST_EXECUTION_ALREADY_COMPLETED', fn () => $service->execute(
            $outcome['admission_id'], $outcome->continuation, $payload, $key, $at + 1,
            static function () use (&$calls): array { ++$calls; return []; },
        ));
        self::assertSame(1, $calls);
        self::assertSame($receipt, $service->reconstruct($receipt['receipt_id'])['receipt']);
    }

    public function testSealedResponseRequiresClaimedForwardRecoveryAndNeverReinvokesCallback(): void
    {
        [$outcome, $continuations, $at, $payload, $key] = $this->admittedForBatch3();
        $calls = 0;
        $service = new NativeEffectDoubleExecutionService($this->state, $continuations, static function (string $cut): void {
            if ('response.sealed' === $cut) {
                throw new \RuntimeException('synthetic process loss');
            }
        });
        $this->fails('UNKNOWN_REPLAY_PROHIBITED', function () use ($service, $outcome, $payload, $key, $at, &$calls): void {
            $service->execute($outcome['admission_id'], $outcome->continuation, $payload, $key, $at, static function () use (&$calls, $at): array {
                ++$calls;
                return ['http_status' => 202, 'headers' => [], 'body' => '{"message_id":"m2","thread_id":"t2"}', 'observed_at' => $at, 'received_at' => $at];
            });
        });

        $fresh = new NativeEffectContinuationCapabilityIssuer();
        $this->fails('CNE508_FORWARD_RECOVERY_REQUIRED', fn () => (new NativeEffectDoubleExecutionService($this->state, $fresh))->execute(
            $outcome['admission_id'], $outcome->continuation, $payload, $key, $at + 1,
            static function () use (&$calls): array { ++$calls; return []; },
        ));

        $claim = $this->admitRecoveryAuthority($outcome->admission, $at, $at + 1);
        $recovery = new NativeEffectForwardRecoveryService($this->state);
        $receipt = $recovery->forwardComplete($claim['claim_id'], $at + 1);
        self::assertSame('ACCEPTED', $receipt['provider_outcome']['status']);
        self::assertSame($receipt, $recovery->forwardComplete($claim['claim_id'], $at + 2));
        self::assertSame(1, $calls);
        self::assertFalse($receipt['recovery']['provider_reinvoked']);
    }

    public function testReconciliationAuthorityMustDenyEveryEffectPower(): void
    {
        [$outcome, $continuations, $at, $payload, $key] = $this->admittedForBatch3();
        $service = new NativeEffectDoubleExecutionService($this->state, $continuations, static function (string $cut): void {
            if ('response.sealed' === $cut) { throw new \RuntimeException('cut'); }
        });
        $this->fails('UNKNOWN_REPLAY_PROHIBITED', fn () => $service->execute(
            $outcome['admission_id'], $outcome->continuation, $payload, $key, $at,
            static fn (): array => ['http_status' => 202, 'headers' => [], 'body' => '{"message_id":"m3","thread_id":"t3"}', 'observed_at' => $at, 'received_at' => $at],
        ));
        $issued = Support\ReconciliationAuthorityFixture::issue($this->state, $outcome['admission_id'], $at + 1, $at + 101);
        foreach (NativeEffectReconciliationAuthorityV2Contract::REQUIRED_FALSE_FLAGS as $flag) {
            self::assertFalse($issued['authority'][$flag]);
        }
        $resolver = new NativeEffectReconciliationAuthorityResolver($this->state);
        $admission = new NativeEffectForwardRecoveryClaimAdmissionService($this->state, $resolver);
        try {
            $admission->admit($issued['authority'], $at + 1);
            self::fail('Caller-authored arrays must be unadmittable.');
        } catch (\TypeError) {
            self::assertTrue(true);
        }
    }

    public function testRecoveryApiHasNoContinuationCallbackPayloadOrKeyInput(): void
    {
        $parameters = array_map(
            static fn (\ReflectionParameter $parameter): string => $parameter->getName(),
            (new \ReflectionMethod(NativeEffectForwardRecoveryService::class, 'forwardComplete'))->getParameters(),
        );
        self::assertSame(['claimId', 'at'], $parameters);
        foreach (['continuation', 'callback', 'payload', 'idempotencyKey', 'providerDouble'] as $forbidden) {
            self::assertNotContains($forbidden, $parameters);
        }
    }

    public function testRecoverySourcesContainNoProviderCredentialOrEnvironmentEdge(): void
    {
        $source = $this->read('src/Imperium/Runtime/ProviderTransition/NativeEffectForwardRecoveryClaimAdmissionService.php')
            .$this->read('src/Imperium/Runtime/ProviderTransition/NativeEffectForwardRecoveryService.php')
            .$this->read('src/Imperium/Runtime/ProviderTransition/NativeEffectReceiptBindingService.php');
        foreach (['CredentialBroker', 'AgentMailEmailTransport', 'HttpClient', 'curl_', 'getenv(', '$_ENV', '$_SERVER'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source, $forbidden);
        }
    }

    public function testBatchDocumentationPreservesBatchFourAndLiveStops(): void
    {
        $docs = $this->read('docs/canonical-native-effect-process-custody-formal-closure-remediation-batch-3-execution-recovery-separation-v1.md')
            .$this->read('docs/handoffs/canonical-native-effect-process-custody-formal-closure-remediation-batch-3-complete.md');
        foreach (['BATCH_3_FIRST_EXECUTION', 'BATCH_4_ADVERSARIAL_APPLICATION_PROOF_NEXT', 'BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED', 'Provider doubles and disposable storage only'] as $marker) {
            self::assertStringContainsStringIgnoringCase($marker, $docs, $marker);
        }
    }

    private function admittedForBatch3(): array
    {
        [$transitionAuthority, $at] = $this->readyTransition();
        $native = (new NativeConsumer($this->state, static fn () => $at))->execute($transitionAuthority);
        $authority = $this->effectAuthority($native['root'], $at);
        $credentials = new NativeEffectCredentialCapabilityIssuer();
        $continuations = new NativeEffectContinuationCapabilityIssuer();
        $outcome = (new NativeEffectAtomicAdmissionService($this->state, $credentials, $continuations))->admit(
            $authority,
            $credentials->issue($authority, $authority['execution_boundary']['id'], $at),
            $at,
        );
        return [$outcome, $continuations, $at, '{"to":["disposable@example.test"]}', 'disposable-idempotency-key'];
    }

    private function admitRecoveryAuthority(array $admission, int $responseAt, int $recoveryAt): array
    {
        $issued = Support\ReconciliationAuthorityFixture::issue(
            $this->state,
            $admission['admission_id'],
            $recoveryAt,
            $recoveryAt + 100,
        );
        $resolver = new NativeEffectReconciliationAuthorityResolver($this->state);
        return (new NativeEffectForwardRecoveryClaimAdmissionService($this->state, $resolver))->admit(
            $resolver->resolve($issued['authority']['authority_id'], $recoveryAt),
            $recoveryAt,
        );
    }

    private function read(string $path): string
    {
        return str_replace(["\r\n", "\r"], "\n", (string) file_get_contents(dirname(__DIR__, 3).'/'.$path));
    }
}
