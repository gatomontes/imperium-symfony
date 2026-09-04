<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\NativeEffect\CanonicalNativeEffectCorridor;
use App\Imperium\Runtime\ProviderTransition\NativeConsumer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectAtomicAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectContinuationCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectCredentialCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectDoubleExecutionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectForwardRecoveryClaimAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectForwardRecoveryService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityContract;
use App\Imperium\Runtime\ProviderTransition\NativeState;
use App\Tests\Imperium\Runtime\Support\CanonicalNativeEffectCorridorKernel;

require_once __DIR__.'/CanonicalNativeEffectCorridorActivationBatch4Test.php';
require_once __DIR__.'/Support/CanonicalNativeEffectCorridorKernel.php';

final class CanonicalNativeEffectProcessCustodyFormalClosureRemediationBatch4Test extends CanonicalNativeEffectCorridorActivationBatch4Test
{
    public function testMissingExpiredAndLineageSubstitutedRecoveryAuthorityFailClosed(): void
    {
        [$admission, $at] = $this->sealedResponseFixture();
        $recovery = new NativeEffectForwardRecoveryService($this->state);
        $this->fails('PST112_IMMUTABLE_RECORD_ABSENT', fn () => $recovery->forwardComplete('missing-forward-recovery-claim', $at + 1));

        $expired = $this->reconciliationAuthority($admission, $at, $at + 1);
        $expired['expires_at'] = $at + 1;
        $expired = NativeState::seal($expired);
        $this->fails('CNE509_RECONCILIATION_AUTHORITY_INVALID', fn () => (new NativeEffectForwardRecoveryClaimAdmissionService($this->state))->admit($expired, $at + 1));

        $substituted = $this->reconciliationAuthority($admission, $at, $at + 1);
        $substituted['sealed_response']['digest'] = str_repeat('0', 64);
        $substituted = NativeState::seal($substituted);
        $this->fails('CNE510_RECONCILIATION_LINEAGE_INVALID', fn () => (new NativeEffectForwardRecoveryClaimAdmissionService($this->state))->admit($substituted, $at + 1));

        self::assertSame([], glob($this->root.'/'.NativeEffectDoubleExecutionService::RECEIPTS.'/*.json') ?: []);
    }

    public function testExactRecoveryClaimIsSinglePurposeAndReplayReturnsOneReceipt(): void
    {
        [$admission, $at, $calls] = $this->sealedResponseFixture();
        $claims = new NativeEffectForwardRecoveryClaimAdmissionService($this->state);
        $claim = $claims->admit($this->reconciliationAuthority($admission, $at, $at + 1), $at + 1);
        $recovery = new NativeEffectForwardRecoveryService($this->state);
        $first = $recovery->forwardComplete($claim['claim_id'], $at + 1);
        $second = $recovery->forwardComplete($claim['claim_id'], $at + 2);

        self::assertSame($first, $second);
        self::assertSame(1, $calls());
        self::assertFalse($first['recovery']['provider_reinvoked']);
        self::assertFalse($claim['provider_invocation_permitted']);
        self::assertFalse($claim['credential_resolution_permitted']);
        self::assertCount(1, glob($this->root.'/'.NativeEffectDoubleExecutionService::RECEIPTS.'/*.json') ?: []);
    }

    public function testRealContainerCorridorCreatesFreshCustodyAndNoProviderRecoveryServices(): void
    {
        $kernel = new CanonicalNativeEffectCorridorKernel($this->root);
        try {
            $kernel->boot();
            $corridor = $kernel->getContainer()->get(CanonicalNativeEffectCorridor::class);
            $first = $corridor->continuationIssuer();
            $second = $corridor->continuationIssuer();
            self::assertInstanceOf(NativeEffectContinuationCapabilityIssuer::class, $first);
            self::assertNotSame($first, $second);
            self::assertInstanceOf(NativeEffectForwardRecoveryClaimAdmissionService::class, $corridor->recoveryClaimAdmission());
            self::assertInstanceOf(NativeEffectForwardRecoveryService::class, $corridor->forwardRecovery());
        } finally {
            $kernel->shutdown();
        }
    }

    public function testCombinedProofManifestNamesEveryRequiredPerimeter(): void
    {
        $proof = $this->readBatch4('docs/canonical-native-effect-process-custody-formal-closure-remediation-batch-4-adversarial-application-proof-v1.md')
            .$this->readBatch4('docs/handoffs/canonical-native-effect-process-custody-formal-closure-remediation-batch-4-complete.md');
        foreach (['SER01-SER05', 'CLN01-CLN03', 'PRC01-PRC09', 'CUT01-CUT11', 'API01-API03', 'REC01-REC08', 'CB01-CB04', 'BND01-BND04', 'WIN01', 'LIN01', 'BATCH_5_SEPARATELY_SEQUENCED_TERMINAL_AUDIT_NEXT', 'BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED'] as $marker) {
            self::assertStringContainsString($marker, $proof, $marker);
        }
    }

    private function sealedResponseFixture(): array
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
        $calls = 0;
        $service = new NativeEffectDoubleExecutionService($this->state, $continuations, static function (string $cut): void {
            if ('response.sealed' === $cut) {
                throw new \RuntimeException('synthetic process loss');
            }
        });
        $this->fails('UNKNOWN_REPLAY_PROHIBITED', function () use ($service, $outcome, $at, &$calls): void {
            $service->execute(
                $outcome['admission_id'],
                $outcome->continuation,
                '{"to":["disposable@example.test"]}',
                'disposable-idempotency-key',
                $at,
                static function () use (&$calls, $at): array {
                    ++$calls;
                    return ['http_status' => 202, 'headers' => [], 'body' => '{"message_id":"proof","thread_id":"proof"}', 'observed_at' => $at, 'received_at' => $at];
                },
            );
        });

        return [$outcome->admission, $at, static fn (): int => $calls];
    }

    private function reconciliationAuthority(array $admission, int $responseAt, int $recoveryAt): array
    {
        $callbackId = 'canonical-native-effect-callback-'.substr(hash('sha256', $admission['admission_id']), 0, 20);
        $responseId = 'canonical-native-effect-response-'.substr(hash('sha256', $callbackId), 0, 20);
        $callback = $this->jsonBatch4(NativeEffectDoubleExecutionService::CALLBACK_STARTS.'/'.$callbackId.'.json');
        $response = $this->jsonBatch4(NativeEffectDoubleExecutionService::RESPONSES.'/'.$responseId.'.json');

        return NativeState::seal([
            'schema' => NativeEffectReconciliationAuthorityContract::SCHEMA,
            'authority_id' => 'native-effect-reconciliation-authority-'.$admission['semantic_effect_tuple_id'],
            'effect_admission' => NativeState::ref($admission, 'admission_id'),
            'callback_start' => NativeState::ref($callback, 'callback_start_id'),
            'sealed_response' => NativeState::ref($response, 'response_id'),
            'deterministic_receipt_id' => NativeEffectForwardRecoveryClaimAdmissionService::receiptId($admission['admission_id']),
            'act' => NativeEffectReconciliationAuthorityContract::ACT,
            'holder' => NativeEffectReconciliationAuthorityContract::HOLDER,
            'issuer' => NativeEffectReconciliationAuthorityContract::ISSUER,
            'effective_at' => $responseAt,
            'expires_at' => $recoveryAt + 100,
            'provider_invocation_permitted' => false,
            'credential_resolution_permitted' => false,
            'callback_reinvocation_permitted' => false,
            'automatic_retry_permitted' => false,
            'single_purpose' => true,
            'sealed' => true,
        ]);
    }

    private function jsonBatch4(string $relative): array
    {
        return json_decode((string) file_get_contents($this->root.'/'.$relative), true, 64, JSON_THROW_ON_ERROR);
    }

    private function readBatch4(string $relative): string
    {
        return str_replace(["\r\n", "\r"], "\n", (string) file_get_contents(dirname(__DIR__, 3).'/'.$relative));
    }
}
