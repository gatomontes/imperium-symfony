<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\{NativeBindingReader, NativeConsumer, NativeState};
use App\Imperium\Runtime\Clavium\DeterministicJournalBoundCredentialBroker;
use App\Imperium\Runtime\LaCortine\{AgentMailIdempotencyHeaderAdapter, CredentialBroker, CredentialCapability, DeterministicEffectStartJournalService};

require_once __DIR__.'/CanonicalConsumerCorrectionBatch1Test.php';
require_once __DIR__.'/IronGateExecutionReceiptBindingBatch6Test.php';

class CanonicalConsumerCorrectionBatch2Test extends CanonicalConsumerCorrectionBatch1Test
{
    protected function joinedFixture(): array
    {
        $at = new \DateTimeImmutable('2026-08-30T20:05:00+00:00');
        $f = (new IronGateExecutionReceiptBindingBatch6Test('testClaimConsumesAuthorizationAndStopsAtDurablePreIoCheckpoint'))->exportPreEffectFixture($this->root, $at);
        $this->bindingAuthorizationTarget = $f[0]['source_authorization'];
        [$id, $timestamp] = $this->readyTransition();
        self::assertSame($at->getTimestamp(), $timestamp);
        return [$id, $timestamp, ...$f];
    }

    public function testEstablishedConsumerInterpretsNativeCommitAndRefusesBeforeCredentialsOrAdmission(): void
    {
        [$id, $at, $claim, $journal, $capability, $payload] = $this->joinedFixture();
        $reader = new NativeBindingReader($this->state);
        $credentials = $this->noCredentials();
        $adapter = new AgentMailIdempotencyHeaderAdapter($reader);
        $consumer = new DeterministicJournalBoundCredentialBroker($this->root, $credentials, $adapter, $reader);
        self::assertSame('BOUND_INACTIVE', $consumer->inspectClaim($claim['claim_id'], new \DateTimeImmutable('@'.$at))['classification']);
        (new NativeConsumer($this->state, static fn () => $at))->execute($id);
        $before = $this->files();
        $r = $consumer->inspectClaim($claim['claim_id'], new \DateTimeImmutable('@'.$at));
        self::assertSame('COMMITTED_CURRENT', $r['classification']);
        self::assertSame($claim['replay_fingerprint'], $r['replay_fingerprint']);
        self::assertSame($claim['execution_identity']['execution_id'], $r['execution_id']);
        self::assertSame($before, $this->files());
        $calls = 0;
        $callback = function () use (&$calls): void { ++$calls; };
        $this->fails('CCI_PRE_EFFECT_ONLY_COMMITTED_CURRENT', fn () => $consumer->invoke($journal['journal_id'], $capability, $payload, new \DateTimeImmutable('@'.$at), $callback));
        // Direct adapter cannot bypass the established consumer; wall time may make it noncurrent.
        try { $adapter->invoke($journal, $claim['request']['destination'], $payload, null, $callback); self::fail('Bypass accepted'); }
        catch (\RuntimeException $e) { self::assertStringStartsWith('CCI_PRE_EFFECT_ONLY_', $e->getMessage()); }
        $this->fails('CCI_PRE_EFFECT_ONLY_COMMITTED_CURRENT', fn () => (new DeterministicEffectStartJournalService($this->root))->start($claim['claim_id'], new \DateTimeImmutable('@'.$at)));
        self::assertSame(0, $credentials->calls);
        self::assertSame(0, $calls);
        self::assertSame([], glob($this->root.'/'.DeterministicJournalBoundCredentialBroker::ADMISSIONS.'/*.json'));
        self::assertSame($before, $this->files());
    }

    public function testCachedLegacyAdmissionAndCallerCandidateCannotBypassNativeInterpretation(): void
    {
        [$id, $at] = $this->readyTransition();
        (new NativeConsumer($this->state, static fn () => $at))->execute($id);
        $ref = NativeState::ref($this->state->json(NativeState::SOURCES['binding'].'/provider-binding.json'), 'binding_id');
        $candidate = ['provider_binding' => $ref];
        $date = new \DateTimeImmutable('@'.$at);
        foreach ([
            new \App\Imperium\Runtime\Imperator\DurableProviderExecutionAuthorityIssuanceService($this->root),
            new \App\Imperium\Runtime\Imperator\ProviderBindingActivationRevocationAuthorityIssuanceService($this->root),
        ] as $service) {
            $this->fails('CCI_NATIVE_STATE_PRECLUDES_LEGACY', fn () => $service->issue('decision-test', $candidate, $date));
        }
        $this->fails('CCI_NATIVE_STATE_PRECLUDES_LEGACY', fn () => (new \App\Imperium\Runtime\LaCortine\SingleOperationProviderBindingActivationIssuanceService($this->root))->activate('decision-test', $candidate, $date));
        $authorityId = 'durable-provider-execution-authority-test';
        $this->write(\App\Imperium\Runtime\Imperator\DurableProviderExecutionAuthorityIssuanceService::AUTHORITIES.'/'.$authorityId.'.json', $candidate);
        foreach ([new \App\Imperium\Runtime\LaCortine\GovernedProviderExecutionAdmissionService($this->root), new \App\Imperium\Runtime\LaCortine\GovernedProviderExecutionCombinedAdmissionService($this->root)] as $service) {
            $this->fails('CCI_NATIVE_STATE_PRECLUDES_LEGACY', fn () => $service->admit($authorityId, $date));
        }
        foreach ([
            [\App\Imperium\Runtime\Clavium\GovernedStationaryCredentialResolutionService::class, \App\Imperium\Runtime\LaCortine\GovernedProviderExecutionAdmissionService::ADMISSIONS, 'governed-provider-execution-admission-'],
            [\App\Imperium\Runtime\Clavium\GovernedStationaryCredentialResolutionV2Service::class, \App\Imperium\Runtime\LaCortine\GovernedProviderExecutionCombinedAdmissionService::ADMISSIONS, 'governed-provider-execution-combined-admission-'],
        ] as [$class, $dir, $prefix]) {
            $admissionId = $prefix.str_repeat('a', 20);
            $this->write($dir.'/'.$admissionId.'.json', $candidate);
            $this->fails('CCI_NATIVE_STATE_PRECLUDES_LEGACY', fn () => (new $class($this->root))->prove($admissionId, $date));
        }
    }

    protected function noCredentials(): CredentialBroker
    {
        return new class implements CredentialBroker {
            public int $calls = 0;
            public function issue(string $credentialRef, string $commissionId, string $operation, \DateTimeImmutable $expiresAt, int $maxUses = 1): CredentialCapability { ++$this->calls; throw new \LogicException('Forbidden credential issue'); }
            public function consume(CredentialCapability $capability, callable $providerOperation): mixed { ++$this->calls; throw new \LogicException('Forbidden credential consume'); }
        };
    }
}
