<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class CuriaServiceConfigurationTest extends TestCase
{
    public function testCoreRegistersGenericCatalogWithoutDirectSeneschalAgent(): void
    {
        $root = dirname(__DIR__, 3);
        $services = file_get_contents($root.'/config/services.yaml');
        $ai = file_get_contents($root.'/config/packages/ai.yaml');

        self::assertIsString($services);
        self::assertIsString($ai);
        self::assertStringContainsString('Symfony\\AI\\Platform\\Bridge\\Generic\\ModelCatalog:', $services);
        self::assertStringContainsString('deepseek-v4-flash:', $services);
        self::assertStringNotContainsString('@ai.agent.seneschal', $services);
        self::assertStringNotContainsString('api_key:', $ai);
        self::assertStringNotContainsString('platform:', $ai);
    }
}
