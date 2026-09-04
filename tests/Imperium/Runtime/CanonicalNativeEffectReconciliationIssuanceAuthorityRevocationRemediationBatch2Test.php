<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\NativeConsumer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectAtomicAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectContinuationCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectCredentialCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectDoubleExecutionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityIssuanceService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceAuthorityResolver;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceAuthorizationService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuancePublicationService;

require_once __DIR__.'/CanonicalNativeEffectCorridorActivationBatch4Test.php';

final class CanonicalNativeEffectReconciliationIssuanceAuthorityRevocationRemediationBatch2Test extends CanonicalNativeEffectCorridorActivationBatch4Test
{
    public function testRootedDecisionSeparateAuthorityAndTypedCustodyPublishOneExactTarget(): void
    {
        [$admission, $at] = $this->sealedResponse();
        $authorization = (new NativeEffectReconciliationIssuanceAuthorizationService($this->state))
            ->authorize($admission['admission_id'], $at + 1, $at + 100);
        self::assertSame('AUTHORIZED', $authorization['decision']['disposition']);
        self::assertSame('ISSUE_EXACT_RECONCILIATION_AUTHORITY', $authorization['issuance_authority']['permitted_transition']);
        self::assertNotSame($authorization['decision']['decision_id'], $authorization['issuance_authority']['issuance_authority_id']);
        self::assertFalse($authorization['decision']['continuing_authority']);
        self::assertFalse($authorization['issuance_authority']['continuing_authority']);

        $resolver = new NativeEffectReconciliationIssuanceAuthorityResolver($this->state);
        $capability = $resolver->resolve($authorization['issuance_authority']['issuance_authority_id'], $at + 2);
        try { clone $capability; self::fail('clone accepted'); }
        catch (\LogicException $error) { self::assertSame('CNE640_RECONCILIATION_ISSUANCE_CAPABILITY_CLONE_PROHIBITED', $error->getMessage()); }
        try { serialize($capability); self::fail('serialization accepted'); }
        catch (\LogicException $error) { self::assertSame('CNE640_RECONCILIATION_ISSUANCE_CAPABILITY_SERIALIZATION_PROHIBITED', $error->getMessage()); }
        $outcome = (new NativeEffectReconciliationIssuancePublicationService($this->state, $resolver))->publish($capability, $at + 2);

        self::assertSame('AUTHORIZED', $outcome['result']);
        self::assertSame($authorization['decision']['target']['authority_id'], $outcome['established_result']['reconciliation_authority']['authority_id']);
        self::assertSame($authorization['decision']['target']['authority_digest'], $outcome['established_result']['reconciliation_authority']['record_digest']);
        self::assertTrue($outcome['established_result']['issuance_authority_consumption']['consumed']);
        self::assertFalse($outcome['continuing_authority']);
    }

    public function testExactRetryConvergesAndChangedWindowConflictsWithoutSecondWinner(): void
    {
        [$admission, $at] = $this->sealedResponse();
        $service = new NativeEffectReconciliationIssuanceAuthorizationService($this->state);
        $first = $service->authorize($admission['admission_id'], $at + 1, $at + 100);
        $resolver = new NativeEffectReconciliationIssuanceAuthorityResolver($this->state);
        $published = (new NativeEffectReconciliationIssuancePublicationService($this->state, $resolver))->publish(
            $resolver->resolve($first['issuance_authority']['issuance_authority_id'], $at + 2),
            $at + 2,
        );
        $same = $service->authorize($admission['admission_id'], $at + 1, $at + 100);
        self::assertSame($first, $same);
        $retryResolver = new NativeEffectReconciliationIssuanceAuthorityResolver($this->state);
        $retried = (new NativeEffectReconciliationIssuancePublicationService($this->state, $retryResolver))->publish(
            $retryResolver->resolve($same['issuance_authority']['issuance_authority_id'], $at + 3),
            $at + 3,
        );
        self::assertSame('EXACT_RETRY_CONVERGED', $retried['result']);
        self::assertSame($published['established_result'], $retried['established_result']);

        $changed = $service->authorize($admission['admission_id'], $at + 1, $at + 90);
        $changedResolver = new NativeEffectReconciliationIssuanceAuthorityResolver($this->state);
        $this->fails('REFUSED_CONFLICTED', fn () => (new NativeEffectReconciliationIssuancePublicationService($this->state, $changedResolver))->publish(
            $changedResolver->resolve($changed['issuance_authority']['issuance_authority_id'], $at + 3),
            $at + 3,
        ));
        self::assertCount(1, glob($this->root.'/var/imperium/runtime/authority-consumptions/*.json') ?: []);
        self::assertCount(1, glob($this->root.'/'.NativeEffectReconciliationAuthorityIssuanceService::AUTHORITIES.'/*.json') ?: []);
    }

    public function testInterruptionAfterDurableConsumptionPermitsOnlyExactPublicationCompletion(): void
    {
        [$admission, $at] = $this->sealedResponse();
        $authorization = (new NativeEffectReconciliationIssuanceAuthorizationService($this->state))
            ->authorize($admission['admission_id'], $at + 1, $at + 100);
        $resolver = new NativeEffectReconciliationIssuanceAuthorityResolver($this->state);
        $cut = new NativeEffectReconciliationIssuancePublicationService($this->state, $resolver, static function (string $point): void {
            if ('issuance-authority.consumed' === $point) { throw new \RuntimeException('synthetic issuance consumption cut'); }
        });
        $this->fails('synthetic issuance consumption cut', fn () => $cut->publish(
            $resolver->resolve($authorization['issuance_authority']['issuance_authority_id'], $at + 2),
            $at + 2,
        ));
        self::assertSame([], glob($this->root.'/'.NativeEffectReconciliationAuthorityIssuanceService::AUTHORITIES.'/*.json') ?: []);

        $fresh = new NativeEffectReconciliationIssuanceAuthorityResolver($this->state);
        $completed = (new NativeEffectReconciliationIssuancePublicationService($this->state, $fresh))->publish(
            $fresh->resolve($authorization['issuance_authority']['issuance_authority_id'], $at + 3),
            $at + 3,
        );
        self::assertSame('EXACT_RETRY_CONVERGED', $completed['result']);
        self::assertCount(1, glob($this->root.'/'.NativeEffectReconciliationAuthorityIssuanceService::AUTHORITIES.'/*.json') ?: []);
    }

    public function testBatchThreeHasReplacedTheLegacyPublicIssuerSignature(): void
    {
        $method = new \ReflectionMethod(NativeEffectReconciliationAuthorityIssuanceService::class, 'issue');
        self::assertSame(['capability', 'at'], array_map(static fn (\ReflectionParameter $parameter): string => $parameter->getName(), $method->getParameters()));
        self::assertSame(\App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceCapability::class, (string) $method->getParameters()[0]->getType());
    }

    public function testBatchDocumentationPinsTheBoundaryAndNextGate(): void
    {
        $root = dirname(__DIR__, 3);
        $documents = (string) file_get_contents($root.'/docs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-batch-2-rooted-decision-custody-publication-v1.md')
            .(string) file_get_contents($root.'/docs/handoffs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-batch-2-complete.md');
        foreach (['BATCH_2_COMPLETE_ROOTED_DECISION_CUSTODY_AND_ATOMIC_PUBLICATION', 'semantic target', 'Batch 3', 'NO_PROVIDER', 'BATCH_7'] as $marker) {
            self::assertStringContainsStringIgnoringCase($marker, $documents, $marker);
        }
    }

    private function sealedResponse(): array
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
        $execution = new NativeEffectDoubleExecutionService($this->state, $continuations, static function (string $cut): void {
            if ('response.sealed' === $cut) { throw new \RuntimeException('synthetic process loss'); }
        });
        $this->fails('UNKNOWN_REPLAY_PROHIBITED', function () use ($execution, $outcome, $at): void {
            $execution->execute(
                $outcome['admission_id'],
                $outcome->continuation,
                '{"to":["disposable@example.test"]}',
                'disposable-idempotency-key',
                $at,
                static fn (): array => ['http_status' => 202, 'headers' => [], 'body' => '{"message_id":"batch2","thread_id":"batch2"}', 'observed_at' => $at, 'received_at' => $at],
            );
        });
        return [$outcome->admission, $at];
    }
}
