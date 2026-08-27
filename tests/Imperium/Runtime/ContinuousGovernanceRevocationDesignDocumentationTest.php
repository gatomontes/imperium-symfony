<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ContinuousGovernanceRevocationDesignDocumentationTest extends TestCase
{
    public function testEveryDispositionIsDesignOnlyAndDeferredPerimeterRemainsClosed(): void
    {
        $document = (string) file_get_contents(dirname(__DIR__, 3).'/contracts/continuous-governance-revocation-authority-design.md');
        foreach (['RESTRICT', 'INTERRUPT', 'REAUTHORIZE', 'RETIRE'] as $disposition) {
            self::assertSame(1, substr_count($document, '| `'.$disposition.'` |'));
        }
        self::assertSame(4, substr_count($document, '`DESIGN_ONLY`'));
        self::assertStringContainsString('No single actor receives omnibus revocation power.', $document);
        self::assertStringContainsString('Sortie, tool, destination, external effect', $document);
        self::assertStringContainsString('implements no propagation or kill switch', $document);
    }
}
