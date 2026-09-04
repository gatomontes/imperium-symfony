<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\ImperatorPrincipalLifecycleDispositionContract;
use App\Imperium\Runtime\Imperator\ImperatorRuntimePrincipalVersionV3Contract;
use App\Imperium\Runtime\ProviderTransition\NativeConsumer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectAtomicAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectContinuationCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectCredentialCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectDoubleExecutionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectForwardRecoveryClaimAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityIssuanceService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityResolver;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceAuthorityResolver;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceAuthorizationService;
use App\Imperium\Runtime\ProviderTransition\NativePrincipal;
use App\Imperium\Runtime\ProviderTransition\NativeState;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__.'/CanonicalNativeEffectCorridorActivationBatch4Test.php';

final class CanonicalNativeEffectReconciliationIssuanceAuthorityRevocationRemediationBatch3Test extends CanonicalNativeEffectCorridorActivationBatch4Test
{
    public function testPublicIssuerRequiresExactTypedIssuanceCustodyAndCorridorSharesItsResolver(): void
    {
        $method = new \ReflectionMethod(NativeEffectReconciliationAuthorityIssuanceService::class, 'issue');
        self::assertSame(['capability', 'at'], array_map(static fn (\ReflectionParameter $parameter): string => $parameter->getName(), $method->getParameters()));
        self::assertSame(\App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceCapability::class, (string) $method->getParameters()[0]->getType());
        $corridor = new \App\Imperium\Runtime\NativeEffect\CanonicalNativeEffectCorridor($this->state);
        $resolver = $corridor->reconciliationIssuanceAuthorityResolver();
        $issuer = $corridor->reconciliationAuthorityIssuer($resolver);
        self::assertInstanceOf(NativeEffectReconciliationIssuanceAuthorityResolver::class, $resolver);
        self::assertInstanceOf(NativeEffectReconciliationAuthorityIssuanceService::class, $issuer);
    }

    public function testOperatorRootRevocationBetweenIssuanceResolutionAndPublicationRefusesBeforeConsumption(): void
    {
        [$admission, $at] = $this->sealedResponse();
        [$authorization, $resolver, $capability] = $this->issuanceCapability($admission, $at + 1, $at + 100);
        $this->revokeRoot();
        $this->fails('REFUSED_OPERATOR_ROOT_REVOKED', fn () => (new NativeEffectReconciliationAuthorityIssuanceService($this->state, $resolver))->issue($capability, $at + 2));
        self::assertSame([], glob($this->root.'/var/imperium/runtime/authority-consumptions/*.json') ?: []);
        self::assertSame($authorization['decision']['target']['authority_id'], $capability->authorityId);
    }

    public function testOperatorRootRevocationBetweenAuthorityResolutionAndClaimPublicationRefuses(): void
    {
        [$admission, $at] = $this->sealedResponse();
        $issued = $this->issueReconciliationAuthority($admission, $at + 1, $at + 100);
        $resolver = new NativeEffectReconciliationAuthorityResolver($this->state);
        $capability = $resolver->resolve($issued['authority']['authority_id'], $at + 2);
        $this->revokeRoot();
        $this->fails('REFUSED_OPERATOR_ROOT_REVOKED', fn () => (new NativeEffectForwardRecoveryClaimAdmissionService($this->state, $resolver))->admit($capability, $at + 3));
        self::assertSame([], glob($this->root.'/'.NativeEffectForwardRecoveryClaimAdmissionService::CLAIMS.'/*.json') ?: []);
    }

    public function testNativePrincipalRevocationBetweenResolutionAndClaimPublicationRefuses(): void
    {
        [$admission, $at] = $this->sealedResponse();
        $issued = $this->issueReconciliationAuthority($admission, $at + 1, $at + 100);
        $resolver = new NativeEffectReconciliationAuthorityResolver($this->state);
        $capability = $resolver->resolve($issued['authority']['authority_id'], $at + 2);
        $chain = $this->state->get('authorities', (string) ($this->state->get('transitions', $admission['native_root'])['authority_id']));
        $nativePrincipalId = $chain['principal']['id'];
        $act = $this->act;
        $act['action'] = 'REVOKE';
        $act['act_id'] = 'revoke-after-reconciliation-resolution';
        (new NativePrincipal($this->state, static fn (): int => $at + 3))->lifecycle($nativePrincipalId, $this->sign($act));
        $this->fails('REFUSED_NATIVE_PRINCIPAL_REVOKED', fn () => (new NativeEffectForwardRecoveryClaimAdmissionService($this->state, $resolver))->admit($capability, $at + 3));
    }

    public function testSourceGenerationAdvanceBetweenResolutionAndClaimPublicationRefusesAsSuperseded(): void
    {
        [$admission, $at] = $this->sealedResponse();
        $issued = $this->issueReconciliationAuthority($admission, $at + 1, $at + 100);
        $resolver = new NativeEffectReconciliationAuthorityResolver($this->state);
        $capability = $resolver->resolve($issued['authority']['authority_id'], $at + 2);
        $successor = $this->source;
        $successor['principal_version_id'] = 'principal-v2-successor';
        $successor['principal_generation'] = 2;
        $successor['lifecycle']['constituted_at'] = gmdate(DATE_ATOM, $at + 3);
        $successor['lifecycle']['effective_at'] = gmdate(DATE_ATOM, $at + 3);
        $successor = NativeState::seal($successor);
        $this->write(NativeState::SOURCES['principal'].'/'.$successor['principal_version_id'].'.json', $successor);
        $this->fails('REFUSED_SOURCE_SUPERSEDED', fn () => (new NativeEffectForwardRecoveryClaimAdmissionService($this->state, $resolver))->admit($capability, $at + 3));
    }

    #[DataProvider('lifecycleRefusals')]
    public function testEachSourceLifecycleEventRetainsItsExactAtUseRefusal(string $disposition, string $refusal): void
    {
        [$admission, $at] = $this->sealedResponse();
        $issued = $this->issueReconciliationAuthority($admission, $at + 1, $at + 100);
        $resolver = new NativeEffectReconciliationAuthorityResolver($this->state);
        $capability = $resolver->resolve($issued['authority']['authority_id'], $at + 2);
        $this->writeLifecycle($disposition, $at + 3);
        $this->fails($refusal, fn () => (new NativeEffectForwardRecoveryClaimAdmissionService($this->state, $resolver))->admit($capability, $at + 3));
    }

    public static function lifecycleRefusals(): array
    {
        return [
            ['SUSPEND', 'REFUSED_SOURCE_SUSPENDED'],
            ['SUPERSEDE', 'REFUSED_SOURCE_SUPERSEDED'],
            ['REVOKE', 'REFUSED_SOURCE_REVOKED'],
            ['EXPIRE', 'REFUSED_SOURCE_EXPIRED'],
            ['RETIRE', 'REFUSED_SOURCE_RETIRED'],
        ];
    }

    public function testV3LifecycleIntroducedAfterResolutionRefusesMigrationRequired(): void
    {
        $this->source['schema'] = ImperatorRuntimePrincipalVersionV3Contract::SCHEMA;
        $this->source['authority_scope']['provider_executor_principal_activation_decision_authority'] = false;
        $this->source = NativeState::seal($this->source);
        $this->act['source_principal'] = NativeState::ref($this->source, 'principal_version_id');
        $this->act['preserved_scope'] = $this->source['authority_scope'];
        $this->write(NativeState::SOURCES['principal'].'/'.$this->source['principal_version_id'].'.json', $this->source);
        [$admission, $at] = $this->sealedResponse();
        $issued = $this->issueReconciliationAuthority($admission, $at + 1, $at + 100);
        $resolver = new NativeEffectReconciliationAuthorityResolver($this->state);
        $capability = $resolver->resolve($issued['authority']['authority_id'], $at + 2);
        $this->writeLifecycle('SUSPEND', $at + 3);
        $this->fails('REFUSED_SOURCE_MIGRATION_REQUIRED', fn () => (new NativeEffectForwardRecoveryClaimAdmissionService($this->state, $resolver))->admit($capability, $at + 3));
    }

    public function testExpiryBoundaryRemainsCapabilityBounded(): void
    {
        [$admission, $at] = $this->sealedResponse();
        [, $resolver, $capability] = $this->issuanceCapability($admission, $at + 1, $at + 10);
        $this->fails('CNE644_RECONCILIATION_ISSUANCE_CAPABILITY_INVALID', fn () => (new NativeEffectReconciliationAuthorityIssuanceService($this->state, $resolver))->issue($capability, $at + 10));
    }

    public function testBatchThreeDocumentationPinsTheExactCurrentnessBoundary(): void
    {
        $root = dirname(__DIR__, 3);
        $documents = (string) file_get_contents($root.'/docs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-batch-3-typed-issuer-at-use-currentness-v1.md')
            .(string) file_get_contents($root.'/docs/handoffs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-batch-3-complete.md');
        foreach (['BATCH_3_COMPLETE_TYPED_ISSUER_AND_AT_USE_CURRENTNESS', 'REFUSED_SOURCE_SUSPENDED', 'REFUSED_SOURCE_MIGRATION_REQUIRED', 'RR02', 'Batch 4', 'BATCH_7'] as $marker) {
            self::assertStringContainsStringIgnoringCase($marker, $documents, $marker);
        }
    }

    private function issuanceCapability(array $admission, int $at, int $expiresAt): array
    {
        $authorization = (new NativeEffectReconciliationIssuanceAuthorizationService($this->state))->authorize($admission['admission_id'], $at, $expiresAt);
        $resolver = new NativeEffectReconciliationIssuanceAuthorityResolver($this->state);
        return [$authorization, $resolver, $resolver->resolve($authorization['issuance_authority']['issuance_authority_id'], $at)];
    }

    private function revokeRoot(): void
    {
        $path = $this->root.'/'.NativeState::TRUST.'/identity.json';
        $anchor = json_decode((string) file_get_contents($path), true, 32, JSON_THROW_ON_ERROR);
        $anchor['revoked'] = true;
        file_put_contents($path, json_encode($anchor, JSON_THROW_ON_ERROR));
    }

    private function writeLifecycle(string $disposition, int $at): void
    {
        $successor = in_array($disposition, ['RENEW', 'SUPERSEDE'], true)
            ? ['id' => 'source-successor', 'digest' => str_repeat('f', 64), 'schema' => $this->source['schema']]
            : null;
        $record = NativeState::seal([
            'schema' => ImperatorPrincipalLifecycleDispositionContract::SCHEMA,
            'disposition_id' => 'source-lifecycle-'.strtolower($disposition),
            'instance_id' => $this->source['instance_id'],
            'operator_root' => $this->source['source_operator_root'],
            'source_principal_version' => NativeState::ref($this->source, 'principal_version_id'),
            'source_status' => 'ACTIVE',
            'disposition' => $disposition,
            'rationale' => 'Synthetic at-use currentness proof.',
            'effective_at' => gmdate(DATE_ATOM, $at),
            'successor_principal_version' => $successor,
            'authority_scope_changed' => false,
            'historical_attribution_preserved' => true,
            'caller_authority_issuance_permitted_after_effective_at' => false,
            'external_action_performed' => false,
            'sealed' => true,
        ]);
        $this->write(NativeState::SOURCES['lifecycle'].'/'.$record['disposition_id'].'.json', $record);
    }

    private function sealedResponse(): array
    {
        [$transitionAuthority, $at] = $this->readyTransition();
        $native = (new NativeConsumer($this->state, static fn () => $at))->execute($transitionAuthority);
        $authority = $this->effectAuthority($native['root'], $at);
        $credentials = new NativeEffectCredentialCapabilityIssuer();
        $continuations = new NativeEffectContinuationCapabilityIssuer();
        $outcome = (new NativeEffectAtomicAdmissionService($this->state, $credentials, $continuations))->admit($authority, $credentials->issue($authority, $authority['execution_boundary']['id'], $at), $at);
        $execution = new NativeEffectDoubleExecutionService($this->state, $continuations, static function (string $cut): void {
            if ('response.sealed' === $cut) { throw new \RuntimeException('synthetic process loss'); }
        });
        $this->fails('UNKNOWN_REPLAY_PROHIBITED', fn () => $execution->execute(
            $outcome['admission_id'], $outcome->continuation, '{"to":["disposable@example.test"]}', 'disposable-idempotency-key', $at,
            static fn (): array => ['http_status' => 202, 'headers' => [], 'body' => '{"message_id":"batch3","thread_id":"batch3"}', 'observed_at' => $at, 'received_at' => $at],
        ));
        return [$outcome->admission, $at];
    }
}
