<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceAuthorityContract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceAuthorizationService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceDecisionContract;
use App\Imperium\Runtime\ProviderTransition\NativePrincipal;
use App\Imperium\Runtime\ProviderTransition\NativeConsumer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectAtomicAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectContinuationCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectCredentialCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectDoubleExecutionService;
use App\Tests\Imperium\Runtime\Support\ReconciliationMissionFixture;

require_once __DIR__.'/CanonicalNativeEffectCorridorActivationBatch4Test.php';

final class CanonicalNativeEffectReconciliationSharedExclusionRemediationBatch2Test extends CanonicalNativeEffectCorridorActivationBatch4Test
{
    public function testDecisionAndIssuanceAuthorityPublishFromCurrentLineageInsideSharedExclusion(): void
    {
        [$admission, $at] = $this->sealedResponseForBatch();
        $cuts = [];
        $result = (new NativeEffectReconciliationIssuanceAuthorizationService($this->state, static function (string $cut) use (&$cuts): void {
            $cuts[] = $cut;
        }))->authorize(...ReconciliationMissionFixture::arguments($admission['admission_id'], $at + 1, $at + 100));
        self::assertSame(NativeEffectReconciliationIssuanceDecisionContract::REQUIRED_FIELDS, array_keys($result['decision']));
        self::assertSame(NativeEffectReconciliationIssuanceAuthorityContract::REQUIRED_FIELDS, array_keys($result['issuance_authority']));
        self::assertSame(['currentness.passed', 'decision.published', 'issuance_authority.published'], $cuts);
        self::assertSame($result['decision']['target'], $result['issuance_authority']['target']);
        self::assertFalse($result['issuance_authority']['consumed']);
    }

    public function testDP01MutationCannotEnterAfterCurrentnessWhilePublisherHoldsSharedExclusion(): void
    {
        [$admission, $at] = $this->sealedResponseForBatch();
        $attempted = false;
        $service = new NativeEffectReconciliationIssuanceAuthorizationService($this->state, function (string $cut) use (&$attempted, $admission, $at): void {
            if ('currentness.passed' !== $cut) { return; }
            $attempted = true;
            $commit = $this->state->get('transitions', $admission['native_root']);
            $chain = $this->state->get('authorities', $commit['authority_id']);
            $act = $this->act;
            $act['action'] = 'REVOKE';
            $act['act_id'] = 'dp01-nested-revocation';
            $this->fails('NIR_NESTED_LOCK', fn () => (new NativePrincipal($this->state, static fn (): int => $at + 2))->lifecycle($chain['principal']['id'], $this->sign($act)));
            self::assertNull($this->state->get('revocations', $chain['principal']['id']));
        });
        $published = $service->authorize(...ReconciliationMissionFixture::arguments($admission['admission_id'], $at + 1, $at + 100));
        self::assertTrue($attempted);
        self::assertSame('AUTHORIZED', $published['decision']['disposition']);
    }

    private function sealedResponseForBatch(): array
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
            static fn (): array => ['http_status' => 202, 'headers' => [], 'body' => '{"message_id":"batch-2","thread_id":"batch-2"}', 'observed_at' => $at, 'received_at' => $at],
        ));
        return [$outcome->admission, $at];
    }
}
