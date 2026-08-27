<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class CurianGovernanceCognitionBoundaryTest extends TestCase
{
    public function testAudienceAndDeliberationAuthoritiesAreDistinctAndSeneschalIsNotDirectlyInjected(): void
    {
        $root = dirname(__DIR__, 3);
        $authority = (string) file_get_contents($root.'/src/Imperium/Runtime/Curia/CurianCognitionAuthorityService.php');
        $resolver = (string) file_get_contents($root.'/src/Imperium/Runtime/Curia/CurianGovernanceCognitionAuthorityResolver.php');
        $gateway = (string) file_get_contents($root.'/src/Imperium/Runtime/Curia/SymfonyAiSeneschalCognitionGateway.php');
        $services = (string) file_get_contents($root.'/config/services.yaml');
        $ai = (string) file_get_contents($root.'/config/packages/ai.yaml');
        $inventory = json_decode((string) file_get_contents($root.'/docs/credential-boundary-agent-inventory.json'), true, 512, JSON_THROW_ON_ERROR);

        self::assertStringContainsString("'audience-opening'", $authority);
        self::assertStringContainsString("'deliberation-turn'", $authority);
        self::assertStringContainsString("'imperator_authorization' => false", $authority);
        self::assertStringContainsString("'execution_authority' => false", $authority);
        self::assertStringContainsString('currentSeneschal', $resolver);
        self::assertStringContainsString('GCA913_CURIAN_LINEAGE_INVALID', $resolver);
        self::assertStringContainsString('GovernanceCognitionInvoker', $gateway);
        self::assertStringContainsString("'assess-imperator-request'", $gateway);
        self::assertStringContainsString("'advance-curian-planning'", $gateway);
        self::assertStringNotContainsString('AgentInterface', $gateway);
        self::assertStringContainsString('CurianGovernanceCognitionAuthorityResolver', $services);
        self::assertStringNotContainsString('@ai.agent.seneschal', $services);
        self::assertStringNotContainsString('seneschal:', $ai);
        self::assertSame(['sortie'], array_column($inventory['definitions'], 'agent'));
        self::assertFalse($inventory['system_wide_gate_closed']);
        self::assertCount(2, $inventory['platform_definitions']);
    }
}
