<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LegateCognitionReferenceMigrationTest extends TestCase
{
    #[DataProvider('serviceProvider')]
    public function testLegateCognitionServiceUsesCanonicalReferenceValidation(
        string $service,
        string $chainError,
    ): void {
        $path = dirname(__DIR__, 3).'/src/Imperium/Runtime/Citadel/'.$service.'.php';
        $source = file_get_contents($path);

        self::assertIsString($source);
        self::assertStringContainsString('RecordReferenceValidator', $source);
        self::assertStringContainsString('->resolve(', $source);
        self::assertStringContainsString('->read(', $source);
        self::assertStringContainsString('->isIntact(', $source);
        self::assertStringContainsString($chainError, $source);
        self::assertStringNotContainsString('file_get_contents', $source);
    }

    public static function serviceProvider(): iterable
    {
        yield 'commission issuance' => ['LegateGovernedCommissionIssuanceService', 'CIT306_GOVERNED_COMMISSION_AUTHORITY_INVALID'];
        yield 'commission disposition' => ['LegateGovernedCommissionDispositionService', 'CIT327_GOVERNED_COMMISSION_DISPOSITION_CHAIN_INVALID'];
        yield 'turn authorization' => ['LegateCognitionTurnAuthorizationService', 'CIT349_COGNITION_TURN_AUTHORIZATION_CHAIN_INVALID'];
        yield 'bounded cognition turn' => ['LegateBoundedCognitionTurnService', 'CIT413_COGNITION_TURN_CHAIN_INVALID'];
        yield 'result delivery' => ['LegateCognitionResultDeliveryService', 'CIT425_COGNITION_RESULT_DELIVERY_CHAIN_INVALID'];
    }
}
