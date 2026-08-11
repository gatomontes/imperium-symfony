<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class CuriaServiceConfigurationTest extends TestCase
{
    public function testCoreRegistersGenericCatalogAndSeneschalAgent(): void
    {
        $services = file_get_contents(dirname(__DIR__, 3).'/config/services.yaml');
        $ai = file_get_contents(dirname(__DIR__, 3).'/config/packages/ai.yaml');

        self::assertIsString($services);
        self::assertIsString($ai);
        self::assertStringContainsString('Symfony\AI\Platform\Bridge\Generic\ModelCatalog:', $services);
        self::assertStringContainsString('deepseek-v4-flash:', $services);
        self::assertStringContainsString('$agent: \'@ai.agent.seneschal\'', $services);
        self::assertStringContainsString("model_catalog: 'Symfony\AI\Platform\Bridge\Generic\ModelCatalog'", $ai);
    }
}
