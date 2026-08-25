<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Citadel\DeepSeekDelegateModelConfiguration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DeepSeekDelegateModelConfigurationTest extends TestCase
{
    public function testNormalizesTheOnlySupportedOption(): void
    {
        $validator = new DeepSeekDelegateModelConfiguration();

        self::assertSame(['temperature' => 0.2], $validator->normalize('deepseek-v4-flash', []));
        self::assertSame(['temperature' => 1.0], $validator->normalize('deepseek-v4-flash', ['temperature' => 1]));
    }

    #[DataProvider('invalidConfigurations')]
    public function testRejectsUnsupportedConfigurationBeforeProviderIo(string $model, mixed $configuration): void
    {
        $this->expectExceptionMessage('CT312_DELEGATE_MODEL_CONFIGURATION_INVALID');
        (new DeepSeekDelegateModelConfiguration())->normalize($model, $configuration);
    }

    public static function invalidConfigurations(): iterable
    {
        yield 'unsupported runtime model' => ['deepseek-chat', ['temperature' => 0.2]];
        yield 'unknown option' => ['deepseek-v4-flash', ['temperature' => 0.2, 'tools' => true]];
        yield 'temperature below range' => ['deepseek-v4-flash', ['temperature' => -0.1]];
        yield 'temperature above range' => ['deepseek-v4-flash', ['temperature' => 2.1]];
        yield 'temperature wrong type' => ['deepseek-v4-flash', ['temperature' => '0.2']];
        yield 'temperature not finite' => ['deepseek-v4-flash', ['temperature' => INF]];
        yield 'configuration wrong type' => ['deepseek-v4-flash', 'temperature=0.2'];
    }
}
