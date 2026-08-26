<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class LegateCredentialBoundaryConfigurationTest extends TestCase
{
    public function testLegateCannotReachCredentialBearingSymfonyAgent(): void
    {
        $root = dirname(__DIR__, 3);
        $ai = (string) file_get_contents($root.'/config/packages/ai.yaml');
        $services = (string) file_get_contents($root.'/config/services.yaml');
        $gateway = (string) file_get_contents($root.'/src/Imperium/Runtime/Citadel/SymfonyAiLegateCognitionGateway.php');

        self::assertStringNotContainsString('citadel_legate_deepseek_v4_flash:', $ai);
        self::assertStringNotContainsString('@ai.agent.citadel_legate_deepseek_v4_flash', $services);
        self::assertStringNotContainsString('AgentInterface', $gateway);
        self::assertStringContainsString('LegateClaimBoundCredentialBroker', $gateway);
        self::assertStringContainsString('DeepSeekDelegatePlatformAdapter', $gateway);
    }
}
