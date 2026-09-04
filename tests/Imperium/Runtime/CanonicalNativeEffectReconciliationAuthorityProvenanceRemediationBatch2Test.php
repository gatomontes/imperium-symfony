<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\ProviderTransition\NativeConsumer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectAtomicAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectContinuationCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectCredentialCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectDoubleExecutionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectForwardRecoveryClaimV2Contract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityClaimDerivationService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityIssuanceService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityResolver;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityV2Contract;
use App\Imperium\Runtime\ProviderTransition\NativeState;

require_once __DIR__.'/CanonicalNativeEffectCorridorActivationBatch4Test.php';

final class CanonicalNativeEffectReconciliationAuthorityProvenanceRemediationBatch2Test extends CanonicalNativeEffectCorridorActivationBatch4Test
{
    public function testIssuerResolvesSignedRootedCompetenceAndPersistsSeparateEvidence(): void
    {
        [$admission, $at] = $this->sealedResponse();
        $issued = $this->issueReconciliation($admission['admission_id'], $at + 1, $at + 100);

        self::assertSame(NativeEffectReconciliationAuthorityV2Contract::SCHEMA, $issued['authority']['schema']);
        self::assertSame($admission['native_root'], $issued['authority']['source_native_transition']['id']);
        $commit = $this->state->get('transitions', $admission['native_root']);
        self::assertSame($commit['authority_id'], $issued['authority']['source_native_authority']['id']);
        self::assertSame($issued['authority']['authority_id'], $issued['issuance']['issued_authority']['id']);
        self::assertSame($issued['authority']['record_digest'], $issued['issuance']['issued_authority']['digest']);
        self::assertTrue($issued['issuance']['authority_issued']);
        foreach (['provider_invocation_performed', 'credential_resolution_performed', 'callback_invocation_performed', 'external_io_performed', 'continuing_authority'] as $flag) {
            self::assertFalse($issued['issuance'][$flag]);
        }
    }

    public function testResolverDeliversOnlyExactNonTransferableProcessCustody(): void
    {
        [$admission, $at] = $this->sealedResponse();
        $issued = $this->issueReconciliation($admission['admission_id'], $at + 1, $at + 100);
        $resolver = new NativeEffectReconciliationAuthorityResolver($this->state);
        $capability = $resolver->resolve($issued['authority']['authority_id'], $at + 2);
        self::assertSame($issued['authority']['record_digest'], $capability->authorityDigest);
        self::assertSame(getmypid(), $capability->runtimeProcessId);

        $this->logicFails('CNE620_RECONCILIATION_CAPABILITY_SERIALIZATION_PROHIBITED', static fn (): string => serialize($capability));
        $this->logicFails('CNE620_RECONCILIATION_CAPABILITY_CLONE_PROHIBITED', static fn (): object => clone $capability);

        $lookalike = new \ReflectionClass($capability);
        $copy = $lookalike->newInstance(...array_values([
            $capability->capabilityId, $capability->authorityId, $capability->authorityDigest,
            $capability->issuanceId, $capability->issuanceDigest, $capability->missionId,
            $capability->dossierIdentity, $capability->expiresAt,
            $capability->runtimeProcessId, $capability->processIncarnationBinding,
        ]));
        $this->fails('CNE624_RECONCILIATION_CAPABILITY_INVALID', fn () => $resolver->consume($copy, $at + 2));
    }

    public function testClaimPublicationIsTheAtomicSinglePurposeConsumption(): void
    {
        [$admission, $at] = $this->sealedResponse();
        $issued = $this->issueReconciliation($admission['admission_id'], $at + 1, $at + 100);
        $resolver = new NativeEffectReconciliationAuthorityResolver($this->state);
        $capability = $resolver->resolve($issued['authority']['authority_id'], $at + 2);
        $claim = (new NativeEffectReconciliationAuthorityClaimDerivationService($this->state, $resolver))->derive($capability, $at + 2);

        self::assertSame(NativeEffectForwardRecoveryClaimV2Contract::SCHEMA, $claim['schema']);
        self::assertSame($issued['authority']['authority_id'], $claim['authority_consumption']['authority_id']);
        self::assertSame($issued['authority']['mission_id'], $issued['issuance']['mission_id']);
        self::assertSame($issued['authority']['mission_id'], $claim['mission_id']);
        self::assertSame($claim['mission_id'], $claim['authority_consumption']['mission_id']);
        self::assertSame($issued['authority']['mission_dossier_identity'], $claim['mission_dossier_identity']);
        self::assertSame($claim['claim_id'], $claim['authority_consumption']['claim_id']);
        self::assertSame($issued['issuance']['record_digest'], $claim['authority_issuance']['digest']);
        self::assertFalse($claim['continuing_authority']);
        $this->fails('CNE623_RECONCILIATION_AUTHORITY_CONSUMED', fn () => (new NativeEffectReconciliationAuthorityResolver($this->state))->resolve($issued['authority']['authority_id'], $at + 3));
        $this->fails('CNE624_RECONCILIATION_CAPABILITY_INVALID', fn () => $resolver->consume($capability, $at + 3));
    }

    public function testFreshDigestWithChangedIssuerLabelCannotSubstituteForIssuance(): void
    {
        [$admission, $at] = $this->sealedResponse();
        $issued = $this->issueReconciliation($admission['admission_id'], $at + 1, $at + 100);
        $path = $this->root.'/'.NativeEffectReconciliationAuthorityIssuanceService::AUTHORITIES.'/'.$issued['authority']['authority_id'].'.json';
        $counterfeit = $issued['authority'];
        $counterfeit['issuer_service'] = 'imperium.imperator.native-effect-reconciliation-authority-issuer/counterfeit';
        unset($counterfeit['record_digest']);
        $counterfeit['record_digest'] = hash('sha256', CanonicalJson::encode($counterfeit));
        file_put_contents($path, json_encode($counterfeit, JSON_THROW_ON_ERROR));

        $this->fails('CNE625_RECONCILIATION_ISSUANCE_INVALID', fn () => (new NativeEffectReconciliationAuthorityResolver($this->state))->resolve($issued['authority']['authority_id'], $at + 2));
    }

    public function testRevokedRootAfterIssuanceRefusesFreshResolution(): void
    {
        [$admission, $at] = $this->sealedResponse();
        $issued = $this->issueReconciliation($admission['admission_id'], $at + 1, $at + 100);
        $path = $this->root.'/'.NativeState::TRUST.'/identity.json';
        $anchor = json_decode((string) file_get_contents($path), true, 32, JSON_THROW_ON_ERROR);
        $anchor['revoked'] = true;
        file_put_contents($path, json_encode($anchor, JSON_THROW_ON_ERROR));
        $this->fails('NIR_ROOT_INELIGIBLE', fn () => (new NativeEffectReconciliationAuthorityResolver($this->state))->resolve($issued['authority']['authority_id'], $at + 2));
    }

    public function testBatchTwoSourcesHaveNoCredentialProviderOrNetworkEdge(): void
    {
        $base = dirname(__DIR__, 3).'/src/Imperium/Runtime/ProviderTransition/';
        $source = '';
        foreach ([
            'NativeEffectReconciliationAuthoritySourceResolver.php',
            'NativeEffectReconciliationAuthorityIssuanceService.php',
            'NativeEffectReconciliationAuthorityCapability.php',
            'NativeEffectReconciliationAuthorityResolver.php',
            'NativeEffectReconciliationAuthorityClaimDerivationService.php',
        ] as $file) {
            $source .= file_get_contents($base.$file);
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
