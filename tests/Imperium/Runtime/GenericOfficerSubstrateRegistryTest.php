<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Conscription\GenericOfficerSubstrateRegistry;
use PHPUnit\Framework\TestCase;

final class GenericOfficerSubstrateRegistryTest extends TestCase
{
    public function testValidatesCanonicalGenericOfficerSubstrate(): void
    {
        $substrate = (new GenericOfficerSubstrateRegistry(dirname(__DIR__, 3)))->current();

        self::assertSame('generic-officer', $substrate['id']);
        self::assertSame('1.0.0', $substrate['version']);
        self::assertStringStartsWith('sha256:', $substrate['content_digest']);
    }
}
