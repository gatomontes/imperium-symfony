<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\NativeConsumer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectAdmissionValidator;
use App\Imperium\Runtime\ProviderTransition\NativeEffectAtomicAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectCredentialCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectDoubleExecutionService;
use App\Imperium\Runtime\ProviderTransition\NativeState;
use App\Imperium\Runtime\NativeEffect\CanonicalNativeEffectCorridor;
use App\Tests\Imperium\Runtime\Support\CanonicalNativeEffectCorridorKernel;

require_once __DIR__.'/CanonicalNativeEffectCorridorActivationBatch4Test.php';
require_once __DIR__.'/Support/CanonicalNativeEffectCorridorKernel.php';

final class CanonicalNativeEffectCorridorActivationBatch5Test extends CanonicalNativeEffectCorridorActivationBatch4Test
{
    public function testSeparateProcessesConvergeOnOneAtomicAdmissionWinner(): void
    {
        [$authority, $at] = $this->authorityFixture();
        $fixture = $this->fixtureFile(['authority' => $authority, 'at' => $at]);

        $results = $this->workers(['admit', 'admit'], $fixture);

        $codes = array_values(array_unique(array_column($results, 'code'))); sort($codes);
        self::assertSame([0, 3], $codes);
        self::assertCount(1, glob($this->root.'/'.NativeEffectAtomicAdmissionService::ADMISSIONS.'/*.json') ?: []);
        self::assertStringContainsString('CNE302_EFFECT_AUTHORITY_ALREADY_USED', implode('', array_column($results, 'stdout')));
    }

    public function testSeparateProcessInterruptionBeforeAndAfterAtomicCutIsFailClosed(): void
    {
        [$authority, $at] = $this->authorityFixture();
        $fixture = $this->fixtureFile(['authority' => $authority, 'at' => $at]);

        self::assertSame(71, $this->worker('stop-before-admit', $fixture)['code']);
        self::assertSame([], glob($this->root.'/'.NativeEffectAtomicAdmissionService::ADMISSIONS.'/*.json') ?: []);
        self::assertSame(72, $this->worker('admit-and-exit', $fixture)['code']);
        self::assertCount(1, glob($this->root.'/'.NativeEffectAtomicAdmissionService::ADMISSIONS.'/*.json') ?: []);
        $retry = $this->worker('admit', $fixture);
        self::assertSame(3, $retry['code']);
        self::assertStringContainsString('CNE302_EFFECT_AUTHORITY_ALREADY_USED', $retry['stdout']);
    }

    public function testSeparateProcessLossDuringCallbackLeavesUnknownAndProhibitsReinvocation(): void
    {
        [$authority, $at] = $this->authorityFixture();
        $issuer = new NativeEffectCredentialCapabilityIssuer();
        $capability = $issuer->issue($authority, $authority['execution_boundary']['id'], $at);
        $admission = (new NativeEffectAtomicAdmissionService($this->state, $issuer))->admit($authority, $capability, $at);
        $marker = $this->root.'/unexpected-provider-double-callback.txt';
        $fixture = $this->fixtureFile([
            'authority' => $authority,
            'at' => $at,
            'admission_id' => $admission['admission_id'],
            'payload' => '{"to":["disposable@example.test"]}',
            'idempotency_key' => 'disposable-idempotency-key',
            'unexpected_callback_marker' => $marker,
        ]);

        self::assertSame(73, $this->worker('callback-exit', $fixture)['code']);
        $retry = $this->worker('callback-retry', $fixture);
        self::assertSame(3, $retry['code']);
        self::assertStringContainsString('UNKNOWN_REPLAY_PROHIBITED', $retry['stdout']);
        self::assertFileDoesNotExist($marker);
    }

    public function testExpiryRevocationAndCancellationAllRefuseBeforeAdmission(): void
    {
        [$authority, $at] = $this->authorityFixture();
        foreach (['revocation_reference', 'cancellation_reference'] as $cut) {
            $candidate = $authority;
            $candidate[$cut] = ['id' => $cut.'-test', 'schema' => 'imperium.refusal/v1', 'digest' => str_repeat('a', 64)];
            $candidate = NativeState::seal($candidate);
            $issuer = new NativeEffectCredentialCapabilityIssuer();
            $capability = $issuer->issue($candidate, $candidate['execution_boundary']['id'], $at);
            $this->fails('CNE200_EFFECT_AUTHORITY_INVALID', fn () => (new NativeEffectAtomicAdmissionService($this->state, $issuer))->admit($candidate, $capability, $at));
        }
        $issuer = new NativeEffectCredentialCapabilityIssuer();
        $capability = $issuer->issue($authority, $authority['execution_boundary']['id'], $at);
        $this->fails('CNE200_EFFECT_AUTHORITY_INVALID', fn () => (new NativeEffectAtomicAdmissionService($this->state, $issuer))->admit($authority, $capability, $authority['expires_at']));
        self::assertSame([], glob($this->root.'/'.NativeEffectAtomicAdmissionService::ADMISSIONS.'/*.json') ?: []);
    }

    public function testProductionContainerExposesOnlyTheInertAndDoubleBoundaries(): void
    {
        [, $at] = $this->authorityFixture();
        $kernel = new CanonicalNativeEffectCorridorKernel($this->root);
        try {
            $kernel->boot();
            $container = $kernel->getContainer();
            $corridor = $container->get(CanonicalNativeEffectCorridor::class);
            self::assertInstanceOf(NativeEffectAdmissionValidator::class, $corridor->admissionValidator());
            $issuer = $corridor->capabilityIssuer();
            self::assertInstanceOf(NativeEffectCredentialCapabilityIssuer::class, $issuer);
            self::assertInstanceOf(NativeEffectAtomicAdmissionService::class, $corridor->atomicAdmission($issuer));
            self::assertInstanceOf(NativeEffectDoubleExecutionService::class, $corridor->providerDouble());
        } finally {
            $kernel->shutdown();
        }
    }

    public function testCommandsTransportsLegacyReadersAndWorkerCannotBypassTheCorridor(): void
    {
        $project = dirname(__DIR__, 3);
        $serviceConfig = file_get_contents($project.'/config/services.yaml');
        self::assertStringContainsString("App\\:\n        resource: '../src/'", $serviceConfig);
        self::assertStringContainsString("- '../src/Imperium/Runtime/ProviderTransition/'", $serviceConfig);
        $consumers = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($project.'/src', \FilesystemIterator::SKIP_DOTS)) as $file) {
            if (!$file->isFile() || 'php' !== $file->getExtension()) { continue; }
            $source = file_get_contents($file->getPathname());
            if (str_contains($source, 'NativeEffectAtomicAdmissionService') && !str_ends_with($file->getPathname(), 'NativeEffectAtomicAdmissionService.php')) {
                $consumers[] = $file->getFilename();
            }
        }
        sort($consumers);
        self::assertSame(
            ['CanonicalNativeEffectCorridor.php', 'NativeEffectDoubleExecutionService.php'],
            $consumers,
            'Only the canonical provider-double continuation may consume the effect cut; no command, transport, executor or legacy reader may do so.',
        );
        $worker = file_get_contents(__DIR__.'/Support/canonical_native_effect_worker.php');
        foreach (['AgentMail', 'CredentialBroker', 'HttpClient', 'getenv(', '$_ENV', '$_SERVER', 'curl_'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $worker);
        }
    }

    private function authorityFixture(): array
    {
        [$transitionAuthority, $at] = $this->readyTransition();
        $native = (new NativeConsumer($this->state, static fn () => $at))->execute($transitionAuthority);
        return [$this->effectAuthority($native['root'], $at), $at];
    }

    private function fixtureFile(array $fixture): string
    {
        $path = $this->root.'/canonical-native-effect-worker-fixture.json';
        $fixture['root'] = $this->root;
        file_put_contents($path, json_encode($fixture, JSON_THROW_ON_ERROR));
        return $path;
    }

    /** @return list<array{code: int, stdout: string, stderr: string}> */
    private function workers(array $modes, string $fixture): array
    {
        $processes = [];
        foreach ($modes as $mode) {
            $pipes = [];
            $process = proc_open([PHP_BINARY, __DIR__.'/Support/canonical_native_effect_worker.php', $mode, $fixture], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            self::assertIsResource($process);
            fclose($pipes[0]);
            $processes[] = [$process, $pipes];
        }
        $results = [];
        foreach ($processes as [$process, $pipes]) {
            $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
            $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
            $results[] = ['code' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
        }
        return $results;
    }

    private function worker(string $mode, string $fixture): array
    {
        return $this->workers([$mode], $fixture)[0];
    }
}
