<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Guildhall\ProfileDefinitionRegistry;
use PHPUnit\Framework\TestCase;

final class ProfileDefinitionRegistryTest extends TestCase
{
    public function testValidatesExactVersionedGuildhallDefinitionAndLifecycle(): void
    {
        $definition = (new ProfileDefinitionRegistry(dirname(__DIR__, 3)))->current('guildmaster', 'guildhall.guildmaster');

        self::assertSame('guildhall.guildmaster', $definition['definition_id']);
        self::assertSame('1.0.0', $definition['definition_version']);
        self::assertStringStartsWith('sha256:', $definition['content_digest']);
        self::assertSame('guildhall.guildmaster.definition.approved.1', $definition['approval_attestation_id']);
        self::assertSame('guildhall.guildmaster.definition.current.1', $definition['current_attestation_id']);
    }
}
