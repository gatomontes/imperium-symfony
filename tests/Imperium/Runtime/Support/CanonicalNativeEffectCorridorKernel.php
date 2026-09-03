<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime\Support;

use App\Imperium\Runtime\NativeEffect\CanonicalNativeEffectCorridor;
use App\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/** Production configuration/discovery with only storage and test visibility changed. */
final class CanonicalNativeEffectCorridorKernel extends Kernel
{
    public function __construct(private readonly string $storage)
    {
        parent::__construct('test', true);
    }

    public function getProjectDir(): string { return dirname(__DIR__, 4); }
    public function getCacheDir(): string { return $this->storage.'/corridor-kernel-cache'; }
    public function getBuildDir(): string { return $this->getCacheDir(); }
    public function getLogDir(): string { return $this->storage.'/corridor-kernel-log'; }
    protected function getContainerClass(): string { return parent::getContainerClass().substr(hash('sha256', $this->storage), 0, 12); }
    protected function getKernelParameters(): array { return array_replace(parent::getKernelParameters(), ['kernel.project_dir' => $this->storage]); }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);
        $loader->load(static function (ContainerBuilder $container): void {
            $container->loadFromExtension('framework', ['secret' => 'disposable-corridor-test']);
            $container->getDefinition(CanonicalNativeEffectCorridor::class)->setPublic(true);
        });
    }
}
