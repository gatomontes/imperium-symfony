<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderInvocationClaimReferenceMigrationTest extends TestCase
{
    public function testClaimBoundaryUsesCanonicalRecordValidation(): void
    {
        $path = dirname(__DIR__, 3).'/src/Imperium/Runtime/Clavium/ProviderInvocationClaimService.php';
        $source = file_get_contents($path);

        self::assertIsString($source);
        self::assertStringContainsString('RecordReferenceValidator', $source);
        self::assertStringContainsString('->read(', $source);
        self::assertStringContainsString('->isIntact(', $source);
        self::assertStringContainsString('CLV403_PROVIDER_INVOCATION_CLAIM_CONFLICT', $source);
        self::assertStringContainsString('CLV404_PROVIDER_INVOCATION_CLAIM_CHAIN_INVALID', $source);
        self::assertStringNotContainsString('file_get_contents', $source);
    }
}
