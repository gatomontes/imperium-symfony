<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

/** Retains the Batch 0 source inventory; defect expectations live only in historical JSON. */
final class ActivationDispositionVocabularyTripwireCorrectionPreparationBatch0Test extends TestCase
{
    private const array VALUES = ['QUARANTINED_PENDING_REMEDIATION', 'RETIRE_CORRIDOR'];

    public function testEveryCurrentOccurrenceMatchesTheVersionedPreparationInventory(): void
    {
        $root = dirname(__DIR__, 3);
        $actual = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.'/src/Imperium/Runtime'));
        foreach ($iterator as $file) {
            if (!$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }
            $path = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            foreach (token_get_all((string) file_get_contents($file->getPathname())) as $token) {
                if (!is_array($token)) {
                    continue;
                }
                foreach (self::VALUES as $value) {
                    if (str_contains($token[1], $value)) {
                        self::assertSame(T_CONSTANT_ENCAPSED_STRING, $token[0], $path);
                        self::assertSame("'".$value."'", $token[1], $path);
                        $actual[] = $path."\t".$token[2]."\t".$value;
                    }
                }
            }
        }
        $expected = file(dirname(__DIR__, 3).'/docs/activation-disposition-vocabulary-tripwire-correction-preparation-occurrences-v1.tsv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        array_shift($expected);
        sort($actual, SORT_STRING);
        sort($expected, SORT_STRING);
        self::assertCount(16, $actual);
        self::assertSame($expected, $actual);
    }
}
