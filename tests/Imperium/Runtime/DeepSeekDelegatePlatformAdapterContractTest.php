<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Citadel\DeepSeekDelegateModelConfiguration;
use App\Imperium\Runtime\Citadel\DeepSeekDelegatePlatformAdapter;
use App\Imperium\Runtime\Citadel\DeepSeekSymfonyPlatformAdapter;
use PHPUnit\Framework\TestCase;

final class DeepSeekDelegatePlatformAdapterContractTest extends TestCase
{
    public function testRegistryIdentityIsExplicitlyDeepSeekSpecific(): void
    {
        self::assertSame('deepseek', DeepSeekDelegatePlatformAdapter::PROVIDER);
        self::assertSame('ai.platform.generic.deepseek', DeepSeekDelegatePlatformAdapter::PLATFORM_SERVICE);
        self::assertSame('deepseek-v4-flash', DeepSeekDelegatePlatformAdapter::RUNTIME_MODEL);
        self::assertSame('env:DEEPSEEK_API_KEY', DeepSeekDelegatePlatformAdapter::CREDENTIAL_REFERENCE);
        self::assertSame('deepseek.model.invoke', DeepSeekDelegatePlatformAdapter::OPERATION);
        self::assertTrue(is_a(DeepSeekSymfonyPlatformAdapter::class, DeepSeekDelegatePlatformAdapter::class, true));
    }

    public function testTheAdapterContractAndConfigurationContractNameTheSameOnlyModel(): void
    {
        $configuration = (new DeepSeekDelegateModelConfiguration())->normalize(
            DeepSeekDelegatePlatformAdapter::RUNTIME_MODEL,
            [],
        );

        self::assertSame(['temperature' => 0.2], $configuration);
    }
}
