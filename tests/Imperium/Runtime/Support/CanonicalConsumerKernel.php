<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime\Support;

use App\Kernel;
use App\Imperium\Runtime\LaCortine\CredentialBroker;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\{ContainerBuilder, Definition};

/** Production config/discovery; only storage, clock, effect sentinel and visibility differ. */
final class CanonicalConsumerKernel extends Kernel
{
    public function __construct(private readonly string $storage, private readonly int $at)
    {
        parent::__construct('test', true);
    }

    public function getProjectDir(): string { return dirname(__DIR__, 4); }
    public function getCacheDir(): string { return $this->storage.'/kernel-cache'; }
    public function getBuildDir(): string { return $this->getCacheDir(); }
    public function getLogDir(): string { return $this->storage.'/kernel-log'; }
    protected function getContainerClass(): string { return parent::getContainerClass().substr(hash('sha256', $this->storage), 0, 12); }
    protected function getKernelParameters(): array { return array_replace(parent::getKernelParameters(), ['kernel.project_dir' => $this->storage]); }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);
        $loader->load(function (ContainerBuilder $container): void {
            $container->loadFromExtension('framework', ['secret' => 'disposable-consumer-test']);
            $container->setDefinition('clock', (new Definition(MockClock::class, ['@'.$this->at]))->setPublic(true));
            $container->setDefinition(NoEffectCredentials::class, (new Definition(NoEffectCredentials::class))->setPublic(true));
            $container->setAlias(CredentialBroker::class, NoEffectCredentials::class)->setPublic(true);
            foreach ([
                \App\Imperium\Runtime\Clavium\DeterministicJournalBoundCredentialBroker::class,
                \App\Imperium\Runtime\LaCortine\DeterministicBoundaryExecutor::class,
                \App\Imperium\Runtime\LaCortine\AgentMailIdempotencyHeaderAdapter::class,
                \App\Imperium\Runtime\LaCortine\AgentMailEmailTransport::class,
                \App\Imperium\Runtime\LaCortine\AgentMailProviderRequestEncoder::class,
                \App\Imperium\Runtime\ProviderTransition\NativeBindingReader::class,
                \App\Imperium\Runtime\Imperator\ProviderBindingActivationDecisionService::class,
                \App\Imperium\Runtime\Imperator\ProviderBindingActivationIssuanceService::class,
                \App\Imperium\Runtime\LaCortine\SingleExecutionProviderBindingActivationService::class,
                \App\Imperium\Runtime\LaCortine\SingleOperationProviderBindingActivationIssuanceService::class,
                \App\Imperium\Runtime\Imperator\DurableProviderExecutionAuthorityIssuanceService::class,
                \App\Imperium\Runtime\LaCortine\GovernedProviderExecutionAdmissionService::class,
                \App\Imperium\Runtime\LaCortine\GovernedProviderExecutionCombinedAdmissionService::class,
                \App\Imperium\Runtime\Clavium\GovernedStationaryCredentialResolutionService::class,
                \App\Imperium\Runtime\Clavium\GovernedStationaryCredentialResolutionV2Service::class,
                \App\Imperium\Runtime\Imperator\ProviderBindingActivationRevocationAuthorityIssuanceService::class,
                \App\Imperium\Runtime\Clavium\ProviderBoundCredentialEligibilityService::class,
                \App\Imperium\Runtime\LaCortine\GovernedToolResultReconstructionService::class,
            ] as $id) { $container->getDefinition($id)->setPublic(true); }
        });
    }
}
