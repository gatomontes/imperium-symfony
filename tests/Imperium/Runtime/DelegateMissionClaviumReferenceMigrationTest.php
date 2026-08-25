<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DelegateMissionClaviumReferenceMigrationTest extends TestCase
{
    #[DataProvider('serviceProvider')]
    public function testCredentialAdjacentServiceUsesCanonicalRecordValidation(string $service, string $chainError): void
    {
        $path = dirname(__DIR__, 3).'/src/Imperium/Runtime/Clavium/'.$service.'.php';
        $source = file_get_contents($path);

        self::assertIsString($source);
        self::assertStringContainsString('RecordReferenceValidator', $source);
        self::assertStringContainsString('->read(', $source);
        self::assertStringContainsString('->isIntact(', $source);
        self::assertStringContainsString("'sha256:'", $source);
        self::assertStringContainsString($chainError, $source);
        self::assertStringNotContainsString('file_get_contents', $source);
    }

    public static function serviceProvider(): iterable
    {
        yield 'model access attestation' => ['DelegateMissionModelAccessAttestationService', 'CLV324_DELEGATE_ACCESS_ATTESTATION_CHAIN_INVALID'];
        yield 'provider invocation activation' => ['DelegateMissionProviderInvocationActivationService', 'CLV335_DELEGATE_INVOCATION_ACTIVATION_CHAIN_INVALID'];
    }
}
