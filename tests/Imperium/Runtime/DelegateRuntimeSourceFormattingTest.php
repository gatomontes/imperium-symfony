<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class DelegateRuntimeSourceFormattingTest extends TestCase
{
    private const EXPANDED_CRITICAL_CLASSES = [
        'Conscription/DelegateMissionOperationalManifestationAssemblyService.php',
        'Conscription/DelegateMissionOperationalManifestationSeatBindingService.php',
        'Conscription/DelegateMissionOperationalProfileQualificationService.php',
        'Conscription/ModelBoundOperationalManifestationAssemblyService.php',
        'Conscription/ModelBoundOperationalManifestationSeatBindingService.php',
        'Senate/ModelBoundProfileDispositionAuthorityOpeningService.php',
    ];

    public function testRuntimeSourceRejectsMalformedNamespaceQualifiedConstruction(): void
    {
        $runtime = dirname(__DIR__, 3).'/src/Imperium/Runtime';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($runtime));

        foreach ($iterator as $file) {
            if (!$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }

            $source = (string) file_get_contents($file->getPathname());
            self::assertDoesNotMatchRegularExpression(
                '/\\bnew\\\\[A-Za-z_]/',
                $source,
                $file->getPathname().' contains malformed namespace-qualified construction.',
            );
        }
    }

    public function testExpandedCriticalClassesRemainPhysicallyReadable(): void
    {
        $runtime = dirname(__DIR__, 3).'/src/Imperium/Runtime';

        foreach (self::EXPANDED_CRITICAL_CLASSES as $relative) {
            $lines = file($runtime.'/'.$relative, FILE_IGNORE_NEW_LINES);
            self::assertIsArray($lines);
            self::assertGreaterThan(40, count($lines), $relative.' has been recompressed.');

            $source = implode("\n", $lines);
            self::assertDoesNotMatchRegularExpression(
                '/declare\\(strict_types=1\\);\\s*namespace/',
                $source,
                $relative.' compresses the declaration and namespace.',
            );

            foreach ($lines as $number => $line) {
                self::assertLessThanOrEqual(
                    240,
                    strlen($line),
                    sprintf('%s:%d exceeds the bounded readability limit.', $relative, $number + 1),
                );
            }
        }
    }
}
