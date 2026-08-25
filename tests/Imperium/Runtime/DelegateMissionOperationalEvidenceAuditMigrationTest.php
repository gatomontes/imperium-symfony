<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class DelegateMissionOperationalEvidenceAuditMigrationTest extends TestCase
{
    public function testAuditDelegatesAllRecordValidationToCanonicalValidator(): void
    {
        $path = dirname(__DIR__, 3).'/src/Imperium/Runtime/Audit/DelegateMissionOperationalEvidenceAuditService.php';
        $source = file_get_contents($path);

        self::assertIsString($source);
        self::assertStringContainsString('RecordReferenceValidator', $source);
        self::assertStringContainsString('->resolve(', $source);
        self::assertStringContainsString('->read(', $source);
        self::assertStringContainsString('->requireIntact(', $source);
        self::assertStringContainsString('AUD315_DELEGATE_TERMINAL_CHAIN_INVALID', $source);
        self::assertStringNotContainsString('file_get_contents', $source);
        self::assertStringNotContainsString('CanonicalJson', $source);
    }
}
