<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class SortieServiceConfigurationTest extends TestCase
{
    public function testSortieGatewayExplicitlyBindsAgentAndToolExecutor(): void
    {
        $config = file_get_contents(dirname(__DIR__, 3).'/config/services_sortie.yaml');
        self::assertIsString($config);
        self::assertStringContainsString("$agent: '@ai.agent.sortie'", $config);
        self::assertStringContainsString("$toolExecutor: '@App\\Imperium\\Runtime\\Sortie\\SortieToolExecutor'", $config);
    }
}
