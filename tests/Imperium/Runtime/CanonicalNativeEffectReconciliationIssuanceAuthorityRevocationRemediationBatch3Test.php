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
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityIssuanceService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityResolver;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceAuthorityCapability;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceAuthorityResolver;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceDecisionService;
use App\Imperium\Runtime\ProviderTransition\NativePrincipal;
use App\Imperium\Runtime\ProviderTransition\NativeState;

require_once __DIR__.'/CanonicalNativeEffectCorridorActivationBatch4Test.php';

final class CanonicalNativeEffectReconciliationIssuanceAuthorityRevocationRemediationBatch3Test extends CanonicalNativeEffectCorridorActivationBatch4Test
{
    public function testPublicIssuerAcceptsOnlyTypedIssuanceCapability(): void
    {
        $method = new \ReflectionMethod(NativeEffectReconciliationAuthorityIssuanceService::class, 'issue');
        self::assertSame(NativeEffectReconciliationIssuanceAuthorityCapability::class, (string) $method->getParameters()[0]->getType());
        self::assertSame('capability', $method->getParameters()[0]->getName());
        self::assertCount(2, $method->getParameters());
        $source = (string) file_get_contents((string) $method->getFileName());
        self::assertStringNotContainsString('issue(string $admissionId', $source);
        self::assertStringNotContainsString('int $expiresAt', $source);
    }

    public function testCorridorRequiresSharedIssuanceResolverAndExposesDecisionBoundary(): void
    {
        self::assertSame(NativeEffectReconciliationIssuanceDecisionService::class, (string) (new \ReflectionMethod(CanonicalNativeEffectCorridor::class, 'reconciliationIssuanceDecision'))->getReturnType());
        self::assertSame(NativeEffectReconciliationIssuanceAuthorityResolver::class, (string) (new \ReflectionMethod(CanonicalNativeEffectCorridor::class, 'reconciliationIssuanceAuthorityResolver'))->getReturnType());
        $issuer = new \ReflectionMethod(CanonicalNativeEffectCorridor::class, 'reconciliationAuthorityIssuer');
        self::assertSame(NativeEffectReconciliationIssuanceAuthorityResolver::class, (string) $issuer->getParameters()[0]->getType());
    }

    public function testResolveThenRootRevokeThenClaimUseRefusesInsideGovernedCut(): void
    {
        [$admission, $at] = $this->sealedResponseWithPrincipal();
        [$resolver, $capability] = $this->reconciliationCapability($admission['admission_id'], $at + 1, $at + 100);
        $this->anchor['revoked'] = true;
        $this->write(NativeState::TRUST.'/identity.json', $this->anchor);

        $this->fails('NIR_ROOT_INELIGIBLE', fn () => (new NativeEffectForwardRecoveryClaimAdmissionService($this->state, $resolver))->admit($capability, $at + 2));
    }

    public function testResolveThenNativePrincipalRevokeThenClaimUseRefuses(): void
    {
        [$admission, $at, $principal] = $this->sealedResponseWithPrincipal();
        [$resolver, $capability] = $this->reconciliationCapability($admission['admission_id'], $at + 1, $at + 100);
        $act = $this->act;
        $act['action'] = 'REVOKE';
        $act['act_id'] = 'revoke-native-after-reconciliation-resolution';
        (new NativePrincipal($this->state, static fn () => $at + 2))->lifecycle($principal['principal_version_id'], $this->sign($act));

        $this->fails('NIR_PRINCIPAL_NOT_CURRENT', fn () => (new NativeEffectForwardRecoveryClaimAdmissionService($this->state, $resolver))->admit($capability, $at + 3));
    }

    public function testResolveThenSourceGenerationAdvanceThenClaimUseRefuses(): void
    {
        [$admission, $at] = $this->sealedResponseWithPrincipal();
        [$resolver, $capability] = $this->reconciliationCapability($admission['admission_id'], $at + 1, $at + 100);
        $successor = $this->source;
        $successor['principal_generation']++;
        $successor['principal_version_id'] = 'imperator-principal-generation-'.$successor['principal_generation'];
        $successor['lifecycle']['constituted_at'] = gmdate(DATE_ATOM, $at + 2);
        $successor['lifecycle']['effective_at'] = gmdate(DATE_ATOM, $at + 2);
        $successor = NativeState::seal($successor);
        $this->write(NativeState::SOURCES['principal'].'/'.$successor['principal_version_id'].'.json', $successor);

        $this->fails('NIR_SOURCE_GENERATION_CHANGED', fn () => (new NativeEffectForwardRecoveryClaimAdmissionService($this->state, $resolver))->admit($capability, $at + 3));
    }

    public function testIssuerCutRevalidatesRootInsideNativeExclusion(): void
    {
        [$admission, $at] = $this->sealedResponseWithPrincipal();
        $authorized = (new NativeEffectReconciliationIssuanceDecisionService($this->state))->authorize($admission['admission_id'], $at + 1, $at + 100);
        $resolver = new NativeEffectReconciliationIssuanceAuthorityResolver($this->state);
        $capability = $resolver->resolve($authorized['issuance_authority']['issuance_authority_id'], $at + 1);
        $this->anchor['revoked'] = true;
        $this->write(NativeState::TRUST.'/identity.json', $this->anchor);

        $this->fails('NIR_ROOT_INELIGIBLE', fn () => (new NativeEffectReconciliationAuthorityIssuanceService($this->state, $resolver))->issue($capability, $at + 2));
    }

    /** @return array{0: NativeEffectReconciliationAuthorityResolver, 1: object} */
    private function reconciliationCapability(string $admissionId, int $at, int $expiresAt): array
    {
        $issued = Support\ReconciliationAuthorityFixture::issue($this->state, $admissionId, $at, $expiresAt);
        $resolver = new NativeEffectReconciliationAuthorityResolver($this->state);

        return [$resolver, $resolver->resolve($issued['authority']['authority_id'], $at)];
    }

    private function sealedResponseWithPrincipal(): array
    {
        [$transitionAuthority, $at, $principal] = $this->readyTransition();
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

        return [$outcome->admission, $at, $principal];
    }
}
