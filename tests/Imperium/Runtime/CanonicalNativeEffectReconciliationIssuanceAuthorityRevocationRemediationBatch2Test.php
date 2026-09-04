<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\NativeConsumer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectAtomicAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectContinuationCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectCredentialCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectDoubleExecutionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityIssuanceV2Contract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorizedIssuanceService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceAuthorityResolver;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceDecisionContract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceDecisionService;

require_once __DIR__.'/CanonicalNativeEffectCorridorActivationBatch4Test.php';

final class CanonicalNativeEffectReconciliationIssuanceAuthorityRevocationRemediationBatch2Test extends CanonicalNativeEffectCorridorActivationBatch4Test
{
    public function testRootedDecisionAndSeparateIssuanceAuthorityBindExactTarget(): void
    {
        [$admission, $at] = $this->sealedResponse();
        $authorized = (new NativeEffectReconciliationIssuanceDecisionService($this->state))->authorize($admission['admission_id'], $at + 1, $at + 100);
        $decision = $authorized['decision'];
        $authority = $authorized['issuance_authority'];

        self::assertSame(NativeEffectReconciliationIssuanceDecisionContract::SCHEMA, $decision['schema']);
        self::assertSame('AUTHORIZED', $decision['disposition']);
        self::assertSame('DECIDE_EXACT_RECONCILIATION_AUTHORITY_ISSUANCE', $decision['competent_issuer']['competence']);
        self::assertSame($decision['source_native_principal'], $decision['competent_issuer_provenance']['native_principal']);
        self::assertSame($decision['operator_root_act'], $decision['competent_issuer_provenance']['operator_root_act']);
        self::assertSame($decision['target'], $authority['target']);
        self::assertSame($decision['replay_identity'], $authority['replay_identity']);
        self::assertFalse($decision['continuing_authority']);
        self::assertFalse($authority['continuing_authority']);
    }

    public function testTypedIssuanceCustodyIsExactProcessLocalAndNonTransferable(): void
    {
        [$admission, $at] = $this->sealedResponse();
        $authorized = (new NativeEffectReconciliationIssuanceDecisionService($this->state))->authorize($admission['admission_id'], $at + 1, $at + 100);
        $resolver = new NativeEffectReconciliationIssuanceAuthorityResolver($this->state);
        $capability = $resolver->resolve($authorized['issuance_authority']['issuance_authority_id'], $at + 2);

        self::assertSame($authorized['decision']['record_digest'], $capability->issuanceDecisionDigest);
        self::assertSame($authorized['issuance_authority']['record_digest'], $capability->issuanceAuthorityDigest);
        self::assertSame(getmypid(), $capability->runtimeProcessId);
        $this->logicFails('CNE641_ISSUANCE_CAPABILITY_SERIALIZATION_PROHIBITED', static fn (): string => serialize($capability));
        $this->logicFails('CNE641_ISSUANCE_CAPABILITY_CLONE_PROHIBITED', static fn (): object => clone $capability);

        $copy = (new \ReflectionClass($capability))->newInstance(...array_values((array) $capability));
        $this->fails('CNE645_ISSUANCE_CAPABILITY_INVALID', fn () => $resolver->consume($copy, $at + 2));
    }

    public function testAuthorizedPublicationConsumesExactAuthorityAndPublishesV2Evidence(): void
    {
        [$admission, $at] = $this->sealedResponse();
        $authorized = (new NativeEffectReconciliationIssuanceDecisionService($this->state))->authorize($admission['admission_id'], $at + 1, $at + 100);
        $resolver = new NativeEffectReconciliationIssuanceAuthorityResolver($this->state);
        $capability = $resolver->resolve($authorized['issuance_authority']['issuance_authority_id'], $at + 2);
        $issued = (new NativeEffectReconciliationAuthorizedIssuanceService($this->state, $resolver))->issue($capability, $at + 2);

        self::assertSame(NativeEffectReconciliationAuthorityIssuanceV2Contract::SCHEMA, $issued['issuance']['schema']);
        self::assertSame($authorized['decision']['record_digest'], $issued['issuance']['issuance_decision']['digest']);
        self::assertSame($authorized['issuance_authority']['record_digest'], $issued['issuance']['issuance_authority']['digest']);
        self::assertSame($issued['issuance_authority_consumption']['record_digest'], $issued['issuance']['issuance_authority_consumption']['digest']);
        self::assertSame($issued['authority']['record_digest'], $issued['issuance']['issued_authority']['digest']);
        self::assertTrue($issued['issuance_authority_consumption']['consumed']);
        self::assertFalse($issued['issuance_authority_consumption']['continuing_authority']);
        self::assertFalse($issued['issuance']['continuing_authority']);
    }

    public function testExactFreshProcessRetryConvergesAfterConsumptionCut(): void
    {
        [$admission, $at] = $this->sealedResponse();
        $authorized = (new NativeEffectReconciliationIssuanceDecisionService($this->state))->authorize($admission['admission_id'], $at + 1, $at + 100);
        $resolver = new NativeEffectReconciliationIssuanceAuthorityResolver($this->state);
        $capability = $resolver->resolve($authorized['issuance_authority']['issuance_authority_id'], $at + 2);
        $this->fails('CUT_AFTER_CONSUMPTION', fn () => (new NativeEffectReconciliationAuthorizedIssuanceService(
            $this->state,
            $resolver,
            static function (string $cut): void {
                if ('issuance-authority.consumed' === $cut) { throw new \RuntimeException('CUT_AFTER_CONSUMPTION'); }
            },
        ))->issue($capability, $at + 2));

        $retryResolver = new NativeEffectReconciliationIssuanceAuthorityResolver($this->state);
        $retry = (new NativeEffectReconciliationAuthorizedIssuanceService($this->state, $retryResolver))->issue(
            $retryResolver->resolve($authorized['issuance_authority']['issuance_authority_id'], $at + 3),
            $at + 3,
        );
        self::assertSame($authorized['decision']['record_digest'], $retry['decision']['record_digest']);
        self::assertSame($authorized['issuance_authority']['record_digest'], $retry['issuance_authority']['record_digest']);
    }

    public function testChangedWindowCompetesForSameTargetAndConflictsBeforeSecondPublication(): void
    {
        [$admission, $at] = $this->sealedResponse();
        $service = new NativeEffectReconciliationIssuanceDecisionService($this->state);
        $first = $service->authorize($admission['admission_id'], $at + 1, $at + 100);
        $changed = $service->authorize($admission['admission_id'], $at + 1, $at + 90);

        $firstResolver = new NativeEffectReconciliationIssuanceAuthorityResolver($this->state);
        (new NativeEffectReconciliationAuthorizedIssuanceService($this->state, $firstResolver))->issue(
            $firstResolver->resolve($first['issuance_authority']['issuance_authority_id'], $at + 2),
            $at + 2,
        );
        $changedResolver = new NativeEffectReconciliationIssuanceAuthorityResolver($this->state);
        $this->fails('PST131_AUTHORITY_CONSUMPTION_CONFLICT', fn () => (new NativeEffectReconciliationAuthorizedIssuanceService($this->state, $changedResolver))->issue(
            $changedResolver->resolve($changed['issuance_authority']['issuance_authority_id'], $at + 2),
            $at + 2,
        ));
    }

    public function testBatchTwoPathContainsNoProviderCredentialOrNetworkEdge(): void
    {
        $root = dirname(__DIR__, 3).'/src/Imperium/Runtime/ProviderTransition/';
        $source = '';
        foreach ([
            'NativeEffectReconciliationIssuanceDecisionService.php',
            'NativeEffectReconciliationIssuanceAuthorityCapability.php',
            'NativeEffectReconciliationIssuanceAuthorityResolver.php',
            'NativeEffectReconciliationAuthorizedIssuanceService.php',
            'NativeEffectReconciliationAuthorityRecordFactory.php',
        ] as $file) {
            $source .= file_get_contents($root.$file);
        }
        foreach (['CredentialBroker', 'AgentMailEmailTransport', 'HttpClient', 'curl_', 'getenv(', '$_ENV', '$_SERVER'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source, $forbidden);
        }
    }

    private function sealedResponse(): array
    {
        [$transitionAuthority, $at] = $this->readyTransition();
        $native = (new NativeConsumer($this->state, static fn () => $at))->execute($transitionAuthority);
        $effectAuthority = $this->effectAuthority($native['root'], $at);
        $credentials = new NativeEffectCredentialCapabilityIssuer();
        $continuations = new NativeEffectContinuationCapabilityIssuer();
        $outcome = (new NativeEffectAtomicAdmissionService($this->state, $credentials, $continuations))->admit(
            $effectAuthority,
            $credentials->issue($effectAuthority, $effectAuthority['execution_boundary']['id'], $at),
            $at,
        );
        $execution = new NativeEffectDoubleExecutionService($this->state, $continuations, static function (string $cut): void {
            if ('response.sealed' === $cut) { throw new \RuntimeException('synthetic process loss'); }
        });
        $this->fails('UNKNOWN_REPLAY_PROHIBITED', fn () => $execution->execute(
            $outcome['admission_id'],
            $outcome->continuation,
            '{"to":["disposable@example.test"]}',
            'disposable-idempotency-key',
            $at,
            static fn (): array => ['http_status' => 202, 'headers' => [], 'body' => '{"message_id":"m","thread_id":"t"}', 'observed_at' => $at, 'received_at' => $at],
        ));

        return [$outcome->admission, $at];
    }

    private function logicFails(string $message, callable $action): void
    {
        try { $action(); self::fail('Expected '.$message); }
        catch (\LogicException $error) { self::assertSame($message, $error->getMessage()); }
    }
}
