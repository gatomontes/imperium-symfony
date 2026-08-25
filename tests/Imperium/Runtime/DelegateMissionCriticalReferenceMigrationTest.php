<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DelegateMissionCriticalReferenceMigrationTest extends TestCase
{
    #[DataProvider('criticalBoundaryProvider')]
    public function testCriticalBoundaryUsesCanonicalReferenceValidation(
        string $relativePath,
        string $conflictError,
        string $sourceDigestExpression,
        string $actorExpression,
    ): void {
        $path = dirname(__DIR__, 3).'/'.$relativePath;
        $source = file_get_contents($path);

        self::assertIsString($source);
        self::assertStringContainsString('RecordReferenceValidator', $source);
        self::assertStringContainsString('->resolve(', $source);
        self::assertStringContainsString('->read(', $source);
        self::assertStringContainsString('->isIntact(', $source);
        self::assertStringContainsString($conflictError, $source);
        self::assertStringContainsString($sourceDigestExpression, $source);
        self::assertStringContainsString($actorExpression, $source);
        self::assertStringNotContainsString('file_get_contents', $source);
    }

    public static function criticalBoundaryProvider(): iterable
    {
        yield 'bounded cognition turn' => [
            'src/Imperium/Runtime/Citadel/DelegateMissionBoundedCognitionTurnService.php',
            'CT309_DELEGATE_TURN_CONFLICT',
            "['source_activation']['digest']",
            "['turn_authority']['id']",
        ];

        yield 'terminal return' => [
            'src/Imperium/Runtime/Garrison/DelegateMissionTerminalReturnService.php',
            'GA309_DELEGATE_TERMINAL_RETURN_CONFLICT',
            "['source_return_authorization']['digest']",
            "['constable']['binding_id']",
        ];
    }
}
