<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\NativeConsumer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectAdmissionContract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectAtomicAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectCredentialCapabilityIssuer;

require_once __DIR__.'/CanonicalNativeEffectCorridorActivationBatch2Test.php';

class CanonicalNativeEffectCorridorActivationBatch3Test extends CanonicalNativeEffectCorridorActivationBatch2Test
{
    public function testAuthorityCapabilityAndEffectStartCommitAsOneSecretFreeRecord(): void
    {
        [$transitionAuthority, $at] = $this->readyTransition();
        $native = (new NativeConsumer($this->state, static fn () => $at))->execute($transitionAuthority);
        $authority = $this->effectAuthority($native['root'], $at);
        $issuer = new NativeEffectCredentialCapabilityIssuer();
        $capability = $issuer->issue($authority, $authority['execution_boundary']['id'], $at);

        $admission = (new NativeEffectAtomicAdmissionService($this->state, $issuer))->admit($authority, $capability, $at);

        self::assertSame(NativeEffectAdmissionContract::CHECKPOINT, $admission['effect_start']['checkpoint']);
        self::assertTrue($admission['authority_consumption']['consumed']);
        self::assertTrue($admission['effect_start']['capability_consumed']);
        foreach (['credential_resolved', 'callback_started', 'external_io_may_have_started', 'provider_invoked'] as $field) {
            self::assertFalse($admission['effect_start'][$field]);
        }
        $bytes = json_encode($admission, JSON_THROW_ON_ERROR);
        foreach (['AGENTMAIL_API_KEY', 'Bearer ', 'credential-reference', 'test-secret'] as $excluded) {
            self::assertStringNotContainsString($excluded, $bytes);
        }
    }

    public function testExactReplayConvergesAndChangedCapabilityConflicts(): void
    {
        [$transitionAuthority, $at] = $this->readyTransition();
        $native = (new NativeConsumer($this->state, static fn () => $at))->execute($transitionAuthority);
        $authority = $this->effectAuthority($native['root'], $at);
        $issuer = new NativeEffectCredentialCapabilityIssuer();
        $capability = $issuer->issue($authority, $authority['execution_boundary']['id'], $at);
        $service = new NativeEffectAtomicAdmissionService($this->state, $issuer);
        $winner = $service->admit($authority, $capability, $at);
        self::assertSame($winner, $service->admit($authority, $capability, $at + 600));

        $other = $issuer->issue($authority, $authority['execution_boundary']['id'], $at);
        $this->fails('CNE302_EFFECT_AUTHORITY_ALREADY_USED', fn () => $service->admit($authority, $other, $at));
    }

    public function testExpiredCapabilityRefusesWithoutAdmission(): void
    {
        [$transitionAuthority, $at] = $this->readyTransition();
        $native = (new NativeConsumer($this->state, static fn () => $at))->execute($transitionAuthority);
        $authority = $this->effectAuthority($native['root'], $at);
        $issuer = new NativeEffectCredentialCapabilityIssuer();
        $capability = $issuer->issue($authority, $authority['execution_boundary']['id'], $at);
        $this->fails('CNE200_EFFECT_AUTHORITY_INVALID', fn () => (new NativeEffectAtomicAdmissionService($this->state, $issuer))->admit($authority, $capability, $at + 600));
        self::assertSame([], glob($this->root.'/'.NativeEffectAtomicAdmissionService::ADMISSIONS.'/*.json') ?: []);
    }

    public function testAtomicConsumerHasNoCredentialOrProviderImplementation(): void
    {
        $root = dirname(__DIR__, 3).'/src/Imperium/Runtime/ProviderTransition/';
        $source = file_get_contents($root.'NativeEffectAtomicAdmissionService.php')
            .file_get_contents($root.'NativeEffectCredentialCapabilityIssuer.php');
        foreach (['getenv(', '$_ENV', '$_SERVER', 'AgentMail', 'HttpClient', 'IronGate', 'Lazaretto'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }
}
