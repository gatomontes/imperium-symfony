<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Guildhall\CanonicalGuildhallStaffRegistry;
use App\Imperium\Runtime\Guildhall\ProfileDefinitionRegistry;
use PHPUnit\Framework\TestCase;

final class CanonicalGuildhallStaffRegistryTest extends TestCase
{
    public function testValidatesCanonicalGuildhallStaffPackageAndLifecycleChains(): void
    {
        $root = dirname(__DIR__, 3);
        $package = (new CanonicalGuildhallStaffRegistry($root, new ProfileDefinitionRegistry($root)))->current();

        self::assertSame('guildhall.canonical-staff', $package['package_id']);
        self::assertSame('1.0.0', $package['package_version']);
        self::assertStringStartsWith('sha256:', $package['record_digest']);
    }
}
