<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\{NativeBindingReader, NativeConsumer, NativeState};
use App\Imperium\Runtime\Clavium\DeterministicJournalBoundCredentialBroker;
use App\Tests\Imperium\Runtime\Support\{CanonicalConsumerKernel, NoEffectCredentials};
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;
use App\Tests\Imperium\Runtime\Support\ConsumerProcess;

require_once __DIR__.'/CanonicalConsumerCorrectionBatch2Test.php';

class CanonicalConsumerCorrectionBatch3Test extends CanonicalConsumerCorrectionBatch2Test
{
    public function testProductionApplicationUsesEstablishedConsumerAcrossNativeStatesWithoutEffects(): void
    {
        [$id, $at, $claim, $journal, $capability, $payload] = $this->joinedFixture();
        $kernel = new CanonicalConsumerKernel($this->root, $at);
        try {
            $kernel->boot();
            $container = $kernel->getContainer();
            $application = new Application($kernel);
            $application->setAutoExit(false);
            $tester = new ApplicationTester($application);
            $args = ['command' => 'imperium:email:send-agentmail', '--inspect-claim' => $claim['claim_id']];
            self::assertSame(1, $this->readOnlyRun($tester, $args));
            self::assertSame('BOUND_INACTIVE', json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR)['classification']);
            (new NativeConsumer($this->state, static fn () => $at))->execute($id);
            $before = $this->files();
            self::assertSame(0, $this->readOnlyRun($tester, $args));
            $result = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('COMMITTED_CURRENT', $result['classification']);
            self::assertSame(NativeBindingReader::root('imperium-test', 'provider-binding', 'email.send'), $result['root']);
            self::assertFalse($result['provider_effect_permitted']);
            self::assertFalse($result['retry_authorized']);
            foreach (['credential_capability', 'credential_reference_digest', 'provider_idempotency_key', 'destination', 'payload'] as $secretOrCapability) {
                self::assertStringNotContainsString('"'.$secretOrCapability.'"', $tester->getDisplay());
            }
            self::assertSame($before, $this->files());
            $calls = 0;
            $this->fails('CCI_PRE_EFFECT_ONLY_COMMITTED_CURRENT', fn () => $container->get(DeterministicJournalBoundCredentialBroker::class)->invoke(
                $journal['journal_id'], $capability, $payload, new \DateTimeImmutable('@'.$at), function () use (&$calls): void { ++$calls; }));
            self::assertSame(0, $calls);
            self::assertSame(0, $container->get(NoEffectCredentials::class)->calls);
            $request = new \App\Imperium\Runtime\LaCortine\OutboundRequest('request-test', $claim['source_authorization']['id'], $claim['source_authorization']['digest'], $capability->commissionId, 'email.send', 'bounded refusal test', \App\Imperium\Runtime\LaCortine\OutboundExecutionMode::Deterministic, [$claim['request']['destination']], ['email.send'], [$capability->capabilityId], hash('sha256', $payload), 'agentmail.message/v1', $capability->expiresAt);
            $this->fails('CCI_EMAIL_REQUEST_HAS_NO_BINDING_ROOT', fn () => $container->get(\App\Imperium\Runtime\LaCortine\DeterministicBoundaryExecutor::class)->execute($request, $payload, $capability, $container->get(\App\Imperium\Runtime\LaCortine\AgentMailEmailTransport::class), new \DateTimeImmutable('@'.$at)));
            $this->fails('CCI_EMAIL_TRANSPORT_HAS_NO_BINDING_ROOT', fn () => $container->get(\App\Imperium\Runtime\LaCortine\AgentMailEmailTransport::class)->execute('email.send', $claim['request']['destination'], '{"to":["synthetic@example.test"]}', 'disposable-opaque'));
            $this->assertLegacyServicesRefuse($container, $claim, $capability, $at);
            [$archive, $eligibility] = (new GovernedToolProviderSeparationBatch7Test('testReconstructsCompleteSeparatedChainReadOnly'))->exportArchive($this->root);
            $archivalBefore = $this->files();
            $proof = $container->get(\App\Imperium\Runtime\LaCortine\GovernedToolResultReconstructionService::class)->reconstruct($archive['admission_id'], $eligibility['eligibility_id']);
            self::assertTrue($proof['read_only']);
            foreach (['provider_reinvoked', 'credential_resolved', 'external_io_performed', 'continuing_authority'] as $key) { self::assertFalse($proof[$key]); }
            self::assertSame($archivalBefore, $this->files());
            self::assertSame(1, $tester->run(['command' => 'imperium:email:send-agentmail']));
            self::assertStringContainsString('GOVERNED_EMAIL_SEND_EXECUTOR_UNAVAILABLE', $tester->getDisplay());
            $container->get('clock')->sleep(601);
            self::assertSame(1, $this->readOnlyRun($tester, $args));
            self::assertSame('COMMITTED_NOT_CURRENT', json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR)['classification']);
            $container->get('clock')->modify('-601 seconds');
            $descriptorPath = NativeState::SOURCES['binding'].'/provider-binding.json';
            $descriptor = $this->state->json($descriptorPath);
            $broken = $descriptor; $broken['status'] = 'BOUND_ACTIVE';
            $this->write($descriptorPath, $broken);
            self::assertSame(1, $this->readOnlyRun($tester, $args));
            self::assertSame('CORRUPT', json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR)['classification']);
            $this->write($descriptorPath, $descriptor);
            $root = $result['root'];
            file_put_contents($this->root.'/'.NativeState::DIRECTORY.'/transitions/'.$root.'/commit.pending', '{}');
            self::assertSame(1, $this->readOnlyRun($tester, $args));
            self::assertSame('INCOMPLETE', json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR)['classification']);
            unlink($this->root.'/'.NativeState::DIRECTORY.'/transitions/'.$root.'/commit.pending');
            $claimPath = \App\Imperium\Runtime\LaCortine\DeterministicExecutionClaimService::CLAIMS.'/'.$claim['claim_id'].'.json';
            $changed = $claim; $changed['request']['destination'] = 'https://example.test/substituted';
            $this->write($claimPath, NativeState::seal($changed));
            self::assertSame(1, $this->readOnlyRun($tester, $args));
            self::assertStringContainsString('CCI_INTERPRETATION_UNAVAILABLE UNKNOWN_REPLAY_PROHIBITED', $tester->getDisplay());
            $this->write($claimPath, $claim);
            $duplicate = $descriptor; $duplicate['binding_id'] = 'duplicate-binding';
            $this->write(NativeState::SOURCES['binding'].'/duplicate-binding.json', NativeState::seal($duplicate));
            self::assertSame(1, $this->readOnlyRun($tester, $args));
            self::assertStringContainsString('CCI_INTERPRETATION_UNAVAILABLE', $tester->getDisplay());
            unlink($this->root.'/'.NativeState::SOURCES['binding'].'/duplicate-binding.json');
            $act = $this->act; $act['action'] = 'REVOKE'; $act['act_id'] = 'revoke-consumer-test';
            (new \App\Imperium\Runtime\ProviderTransition\NativePrincipal($this->state, static fn () => $at + 1))->lifecycle($act['target_id'], $this->sign($act));
            $container->get('clock')->sleep(2);
            self::assertSame(1, $this->readOnlyRun($tester, $args));
            self::assertSame('COMMITTED_NOT_CURRENT', json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR)['classification']);
            self::assertSame(0, $container->get(NoEffectCredentials::class)->calls);
            self::assertSame([], glob($this->root.'/'.DeterministicJournalBoundCredentialBroker::CREDENTIAL_ATTEMPTS.'/*.json'));
            self::assertSame([], glob($this->root.'/'.DeterministicJournalBoundCredentialBroker::CALLBACK_STARTS.'/*.json'));
        } finally { $kernel->shutdown(); }
    }

    public function testMalformedNativeDirectoryCannotMasqueradeAsUntouchedInactive(): void
    {
        [$id, $at] = $this->readyTransition();
        // No journal exists yet. A file occupying its directory is corrupt, not absence.
        file_put_contents($this->root.'/'.NativeState::DIRECTORY.'/journals', '{}');
        self::assertSame('CORRUPT', (new NativeBindingReader($this->state))->interpret('imperium-test', 'provider-binding', 'email.send', $at)['classification']);
    }

    public function testAliasedIntactDescriptorCannotChangeTheRootBeingGuarded(): void
    {
        [$id, $at] = $this->readyTransition();
        (new NativeConsumer($this->state, static fn () => $at))->execute($id);
        $other = $this->state->json(NativeState::SOURCES['binding'].'/provider-binding.json');
        $other['binding_id'] = 'other-binding';
        $other = NativeState::seal($other);
        $this->write(NativeState::SOURCES['binding'].'/other-binding.json', $other);
        $alias = 'provider-implementation-binding-'.str_repeat('b', 20);
        $path = NativeState::SOURCES['binding'].'/'.$alias.'.json';
        $this->write($path, $other);
        $reader = new NativeBindingReader($this->state);
        $reader->assertLegacy($other);
        $before = $this->files();
        $validator = new \App\Imperium\Runtime\Persistence\RecordReferenceValidator($this->root, $reader);
        $this->fails('CCI_BINDING_IDENTITY_MISMATCH', fn () => $validator->read($this->root.'/'.$path, 'absent'));
        $reference = NativeState::ref($other, 'binding_id'); $reference['id'] = $alias;
        $this->fails('CCI_BINDING_IDENTITY_MISMATCH', fn () => $reader->assertLegacyRecord(['provider_binding' => $reference]));
        self::assertSame($before, $this->files());
    }

    public function testSeparateProcessNativePublicationExcludesCompetingLegacyAdmission(): void
    {
        [$id, $at] = $this->readyTransition();
        $legacyId = 'durable-provider-execution-authority-test';
        $descriptor = $this->state->json(NativeState::SOURCES['binding'].'/provider-binding.json');
        $this->write(\App\Imperium\Runtime\Imperator\DurableProviderExecutionAuthorityIssuanceService::AUTHORITIES.'/'.$legacyId.'.json', ['provider_binding' => NativeState::ref($descriptor, 'binding_id')]);
        $native = $this->worker('hold', $id, $at);
        $legacy = $this->worker('legacy', $legacyId, $at);
        try {
            $native->start();
            $this->until(fn () => is_file($this->root.'/process-held'));
            self::assertSame('INCOMPLETE', (new NativeBindingReader($this->state))->interpret('imperium-test', 'provider-binding', 'email.send', $at)['classification']);
            $legacy->start();
            $this->until(fn () => str_contains($legacy->getOutput(), 'ATTEMPTING'));
            usleep(200000);
            self::assertTrue($legacy->isRunning(), $legacy->getOutput().$legacy->getErrorOutput());
            self::assertStringNotContainsString('CCI_NATIVE_STATE_PRECLUDES_LEGACY', $legacy->getOutput());
            file_put_contents($this->root.'/process-release', 'release');
            self::assertSame(0, $native->wait(), $native->getErrorOutput());
            self::assertSame(0, $legacy->wait(), $legacy->getErrorOutput());
            self::assertSame('BOUND_ACTIVE_FOR_EXACT_OPERATION', $native->getOutput());
            self::assertStringContainsString('CCI_NATIVE_STATE_PRECLUDES_LEGACY', $legacy->getOutput());
            self::assertCount(1, $this->state->ids('transitions'));
            self::assertSame([], glob($this->root.'/'.\App\Imperium\Runtime\LaCortine\GovernedProviderExecutionAdmissionService::ADMISSIONS.'/*.json'));
        } finally { $native->stop(0); $legacy->stop(0); }
    }

    public function testProcessInterruptionAfterJournalRemainsUnknownAndDoesNotRestart(): void
    {
        [$id, $at] = $this->readyTransition();
        $process = $this->worker('cut', $id, $at);
        self::assertSame(23, $process->run());
        $reader = new NativeBindingReader($this->state);
        $before = $this->files();
        self::assertSame('INCOMPLETE', $reader->interpret('imperium-test', 'provider-binding', 'email.send', $at)['classification']);
        $this->fails('UNKNOWN_REPLAY_PROHIBITED', fn () => (new NativeConsumer($this->state, static fn () => $at))->execute($id));
        self::assertSame($before, $this->files());
        self::assertSame([], $this->state->ids('transitions'));
    }

    public function testBoundClaimCannotSubstituteReplayIdentityOrRequestEvenWhenResealed(): void
    {
        [$id, $at, $claim] = $this->joinedFixture();
        (new NativeConsumer($this->state, static fn () => $at))->execute($id);
        $reader = new NativeBindingReader($this->state);
        foreach (['replay', 'execution', 'request'] as $case) {
            $changed = $claim;
            if ('replay' === $case) { $changed['replay_fingerprint'] = str_repeat('f', 64); }
            elseif ('execution' === $case) { $changed['execution_identity']['execution_id'] = 'different-execution'; }
            else { $changed['request']['destination'] = 'https://example.test/substituted'; }
            $this->write(\App\Imperium\Runtime\LaCortine\DeterministicExecutionClaimService::CLAIMS.'/'.$claim['claim_id'].'.json', NativeState::seal($changed));
            $this->fails('CCI_CLAIM_REPLAY_JOIN_INVALID', fn () => $reader->forClaim($claim['claim_id'], $at));
        }
    }

    private function readOnlyRun(ApplicationTester $tester, array $args): int
    {
        $before = $this->files();
        $exit = $tester->run($args);
        self::assertSame($before, $this->files(), 'Console inspection must not write or repair state.');
        return $exit;
    }

    private function worker(string $mode, string $authority, int $at): ConsumerProcess
    {
        return new ConsumerProcess([PHP_BINARY, __DIR__.'/Support/canonical_consumer_worker.php', $mode, $this->root, $authority, (string) $at], $this->root, $mode);
    }

    private function until(callable $condition): void
    {
        $deadline = microtime(true) + 10;
        while (!$condition()) {
            if (microtime(true) > $deadline) { self::fail('Process rendezvous timeout'); }
            usleep(10000); clearstatcache();
        }
    }

    private function assertLegacyServicesRefuse(\Symfony\Component\DependencyInjection\ContainerInterface $container, array $claim, \App\Imperium\Runtime\LaCortine\CredentialCapability $capability, int $at): void
    {
        $date = new \DateTimeImmutable('@'.$at);
        $descriptor = $this->state->json(NativeState::SOURCES['binding'].'/provider-binding.json');
        $candidate = ['provider_binding' => NativeState::ref($descriptor, 'binding_id')];
        $oldId = 'provider-implementation-binding-'.str_repeat('a', 20);
        $this->write(NativeState::SOURCES['binding'].'/'.$oldId.'.json', $descriptor);
        $this->fails('CCI_NATIVE_STATE_PRECLUDES_LEGACY', fn () => $container->get(\App\Imperium\Runtime\Imperator\ProviderBindingActivationDecisionService::class)->decide('unused-caller', $claim['claim_id'], $oldId, 'AUTHORIZED', 'reason', 'limit', $date->modify('+1 minute'), $date));
        unlink($this->root.'/'.NativeState::SOURCES['binding'].'/'.$oldId.'.json');
        $decisionId = 'provider-binding-activation-decision-'.str_repeat('a', 20);
        $this->write(\App\Imperium\Runtime\Imperator\ProviderBindingActivationDecisionService::DECISIONS.'/'.$decisionId.'.json', $candidate);
        $this->fails('CCI_NATIVE_STATE_PRECLUDES_LEGACY', fn () => $container->get(\App\Imperium\Runtime\Imperator\ProviderBindingActivationIssuanceService::class)->issue('unused-caller', $decisionId, $date));
        $authorityId = 'provider-binding-activation-authority-'.str_repeat('a', 20);
        $this->write(\App\Imperium\Runtime\Imperator\ProviderBindingActivationIssuanceService::ISSUANCES.'/test-issuance.json', ['issued_activation_authority' => ['authority_id' => $authorityId] + $candidate]);
        $this->fails('CCI_NATIVE_STATE_PRECLUDES_LEGACY', fn () => $container->get(\App\Imperium\Runtime\LaCortine\SingleExecutionProviderBindingActivationService::class)->activate($authorityId, $date));
        foreach ([\App\Imperium\Runtime\Imperator\DurableProviderExecutionAuthorityIssuanceService::class, \App\Imperium\Runtime\Imperator\ProviderBindingActivationRevocationAuthorityIssuanceService::class] as $class) {
            $this->fails('CCI_NATIVE_STATE_PRECLUDES_LEGACY', fn () => $container->get($class)->issue('unused-decision', $candidate, $date));
        }
        $this->fails('CCI_NATIVE_STATE_PRECLUDES_LEGACY', fn () => $container->get(\App\Imperium\Runtime\LaCortine\SingleOperationProviderBindingActivationIssuanceService::class)->activate('unused-decision', $candidate, $date));
        $authorityId = 'durable-provider-execution-authority-test';
        $this->write(\App\Imperium\Runtime\Imperator\DurableProviderExecutionAuthorityIssuanceService::AUTHORITIES.'/'.$authorityId.'.json', $candidate);
        foreach ([\App\Imperium\Runtime\LaCortine\GovernedProviderExecutionAdmissionService::class, \App\Imperium\Runtime\LaCortine\GovernedProviderExecutionCombinedAdmissionService::class] as $class) {
            $this->fails('CCI_NATIVE_STATE_PRECLUDES_LEGACY', fn () => $container->get($class)->admit($authorityId, $date));
        }
        foreach ([
            [\App\Imperium\Runtime\Clavium\GovernedStationaryCredentialResolutionService::class, \App\Imperium\Runtime\LaCortine\GovernedProviderExecutionAdmissionService::ADMISSIONS, 'governed-provider-execution-admission-'],
            [\App\Imperium\Runtime\Clavium\GovernedStationaryCredentialResolutionV2Service::class, \App\Imperium\Runtime\LaCortine\GovernedProviderExecutionCombinedAdmissionService::ADMISSIONS, 'governed-provider-execution-combined-admission-'],
        ] as [$class, $dir, $prefix]) {
            $admissionId = $prefix.str_repeat('a', 20);
            $this->write($dir.'/'.$admissionId.'.json', $candidate);
            $this->fails('CCI_NATIVE_STATE_PRECLUDES_LEGACY', fn () => $container->get($class)->prove($admissionId, $date));
        }
        $this->fails('CCI_NATIVE_STATE_PRECLUDES_LEGACY', fn () => $container->get(\App\Imperium\Runtime\Clavium\ProviderBoundCredentialEligibilityService::class)->assess($descriptor, $capability, $date));
        $this->fails('CCI_NATIVE_STATE_PRECLUDES_LEGACY', fn () => $container->get(\App\Imperium\Runtime\LaCortine\AgentMailProviderRequestEncoder::class)->encode($descriptor, $claim['request']['destination'], 'payload', null, 'unused-key'));
        $other = $descriptor; $other['binding_id'] = 'unrelated-binding'; $other['scope']['operation'] = 'http.post.json';
        $other = NativeState::seal($other); $this->write(NativeState::SOURCES['binding'].'/unrelated-binding.json', $other);
        $reader = $container->get(NativeBindingReader::class);
        $reader->assertLegacy($other);
        self::assertSame('UNRELATED_OPERATION', $reader->interpret('imperium-test', 'unrelated-binding', 'email.send', $at)['classification']);
        self::assertSame('BOUND_INACTIVE', $reader->interpret('imperium-test', 'unrelated-binding', 'http.post.json', $at)['classification']);
    }
}
