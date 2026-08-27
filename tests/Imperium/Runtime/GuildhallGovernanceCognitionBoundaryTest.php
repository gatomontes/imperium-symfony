<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class GuildhallGovernanceCognitionBoundaryTest extends TestCase
{
    public function testFourGuildhallStagesAreSeparatelyTypedAndDirectAgentsAreAbsent(): void
    {
        $root = dirname(__DIR__, 3);
        $resolver = file_get_contents($root.'/src/Imperium/Runtime/Guildhall/GuildhallGovernanceCognitionAuthorityResolver.php');
        $gateway = file_get_contents($root.'/src/Imperium/Runtime/Guildhall/SymfonyAiGuildhallCognitionGateway.php');
        $services = file_get_contents($root.'/config/services.yaml');
        $ai = file_get_contents($root.'/config/packages/ai.yaml');
        $inventory = json_decode((string) file_get_contents($root.'/docs/credential-boundary-agent-inventory.json'), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsString($resolver);
        self::assertIsString($gateway);
        foreach ([
            "'disciplinary-fit' => ['disciplinary_fit', 'guildhall.committee.disciplinary-fit'",
            "'composition' => ['composition', 'guildhall.committee.composition'",
            "'boundary-challenge' => ['boundary_challenge', 'guildhall.committee.boundary-challenge'",
            "'guildmaster-synthesis' => ['guildmaster', 'guildhall.guildmaster'",
        ] as $authority) {
            self::assertStringContainsString($authority, $resolver);
        }
        self::assertStringContainsString('GCA803_GUILDHALL_PREDECESSOR_INVALID', $resolver);
        self::assertStringContainsString("'consumed' => \$this->consumed(\$id, \$stage)", $resolver);
        self::assertStringContainsString('GovernanceCognitionInvoker', $gateway);
        self::assertStringNotContainsString('AgentInterface', $gateway);
        self::assertStringContainsString('GuildhallGovernanceCognitionAuthorityResolver', $services);
        self::assertStringNotContainsString('@ai.agent.guildhall_', $services);
        foreach (['guildhall_disciplinary_fit:', 'guildhall_composition:', 'guildhall_boundary_challenge:', 'guildmaster:'] as $definition) {
            self::assertStringNotContainsString($definition, $ai);
        }
        self::assertSame(['seneschal', 'sortie'], array_column($inventory['definitions'], 'agent'));
        self::assertFalse($inventory['system_wide_gate_closed']);
        self::assertCount(2, $inventory['platform_definitions']);
    }
}
