<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Clavium\DeterministicJournalBoundCredentialBroker;
use App\Imperium\Runtime\ProviderTransition\{NativeBindingReader, NativeConsumer};
use App\Tests\Imperium\Runtime\Support\{CanonicalConsumerKernel, NoEffectCredentials};
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

require_once __DIR__.'/CanonicalConsumerCorrectionBatch3Test.php';

final class NativeInspectionSnapshotConsistencyBatch4Test extends CanonicalConsumerCorrectionBatch3Test
{
    public function testProductionContainerAndCliUseTheCoherentReaderWithoutWritesOrEffects(): void
    {
        [$authority, $at, $claim] = $this->joinedFixture();
        $kernel = new CanonicalConsumerKernel($this->root, $at);
        try {
            $kernel->boot();
            $container = $kernel->getContainer();
            $reader = $container->get(NativeBindingReader::class);
            $property = new \ReflectionProperty(NativeBindingReader::class, 'inspectionCheckpoint');
            self::assertNull($property->getValue($reader));

            $application = new Application($kernel);
            $application->setAutoExit(false);
            $tester = new ApplicationTester($application);
            $arguments = ['command' => 'imperium:email:send-agentmail', '--inspect-claim' => $claim['claim_id']];
            $before = $this->files();
            self::assertSame(1, $tester->run($arguments));
            self::assertSame($before, $this->files());
            self::assertSame('BOUND_INACTIVE', json_decode($tester->getDisplay(), true, 32, JSON_THROW_ON_ERROR)['classification']);

            (new NativeConsumer($this->state, static fn (): int => $at))->execute($authority);
            $before = $this->files();
            self::assertSame(0, $tester->run($arguments));
            $result = json_decode($tester->getDisplay(), true, 32, JSON_THROW_ON_ERROR);
            self::assertSame(['root', 'classification', 'descriptor', 'receipt', 'read_only',
                'provider_effect_permitted', 'retry_authorized', 'recovery', 'execution_claim',
                'execution_id', 'replay_fingerprint'], array_keys($result));
            self::assertSame('COMMITTED_CURRENT', $result['classification']);
            self::assertTrue($result['read_only']);
            self::assertFalse($result['provider_effect_permitted']);
            self::assertFalse($result['retry_authorized']);
            self::assertSame('UNKNOWN_REPLAY_PROHIBITED', $result['recovery']);
            self::assertSame($before, $this->files());
            self::assertSame(0, $container->get(NoEffectCredentials::class)->calls);
            foreach (['credential_capability', 'credential_reference_digest', 'provider_idempotency_key', 'payload'] as $secret) {
                self::assertStringNotContainsString('"'.$secret.'"', $tester->getDisplay());
            }
        } finally {
            $kernel->shutdown();
        }
    }

    public function testAlreadyLockedBrokerStillRefusesBeforeCredentialAndCallback(): void
    {
        [$authority, $at, $claim, $journal, $capability, $payload] = $this->joinedFixture();
        (new NativeConsumer($this->state, static fn (): int => $at))->execute($authority);
        $kernel = new CanonicalConsumerKernel($this->root, $at);
        try {
            $kernel->boot();
            $container = $kernel->getContainer();
            $calls = 0;
            $before = $this->files();
            $this->fails('CCI_PRE_EFFECT_ONLY_COMMITTED_CURRENT', fn () => $container->get(DeterministicJournalBoundCredentialBroker::class)->invoke(
                $journal['journal_id'], $capability, $payload, new \DateTimeImmutable('@'.$at), function () use (&$calls): void { ++$calls; }
            ));
            self::assertSame(0, $calls);
            self::assertSame(0, $container->get(NoEffectCredentials::class)->calls);
            self::assertSame($before, $this->files());
            self::assertSame([], glob($this->root.'/'.DeterministicJournalBoundCredentialBroker::CREDENTIAL_ATTEMPTS.'/*.json'));
            self::assertSame([], glob($this->root.'/'.DeterministicJournalBoundCredentialBroker::CALLBACK_STARTS.'/*.json'));
        } finally {
            $kernel->shutdown();
        }
    }

    public function testProductionConfigurationDoesNotBindProofCheckpointOrNewLockService(): void
    {
        $services = file_get_contents(dirname(__DIR__, 3).'/config/services.yaml');
        self::assertNotFalse($services);
        self::assertStringContainsString('NativeBindingReader: ~', $services);
        self::assertStringNotContainsString('inspectionCheckpoint', $services);
        self::assertStringNotContainsString('NativeInspectionSnapshot:', $services);
        $snapshot = file_get_contents(dirname(__DIR__, 3).'/src/Imperium/Runtime/ProviderTransition/NativeInspectionSnapshot.php');
        self::assertNotFalse($snapshot);
        foreach (['AtomicTransition', 'flock(', 'file_put_contents(', 'CredentialBroker', 'ProviderInterface'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $snapshot, $forbidden);
        }
    }
}
