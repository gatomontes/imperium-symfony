<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Onboarding\AuthorityAdmission\StrictJson;
use App\Tests\Imperium\Runtime\Support\EntryStrictJsonScanner;
use PHPUnit\Framework\TestCase;

final class StrictJsonScannerCompatibilityTest extends TestCase
{
    private function outcome(string $class, string $raw): array
    {
        try { return ['value', $class::decode($raw)]; }
        catch (\InvalidArgumentException $e) { return ['error', $e::class, $e->getMessage(), $e->getPrevious()?->getMessage()]; }
    }

    public function testNativeScannerMatchesEntryParserAcrossBoundaries(): void
    {
        $cases = ['', '"', '"\\', '"\\"', '"\\\\"', '"\\u0000"', '"\\uD800"', '"\\uD800\\uDC00"', '"\\uDC00"', '"\\uZZZZ"', '"\\q"', '"a"trailing', '{"a":1,"a":2}', '{"\\u0061":1,"a":2}', '"é漢字"', "\"\xFF\"", "\xEF\xBB\xBF\"a\""];
        for ($byte = 0; $byte < 256; ++$byte) {
            $cases[] = '"prefix'.chr($byte).'suffix"';
            $cases[] = '"prefix\\'.chr($byte).'suffix"';
        }
        foreach ([0, 1, 2, 3, 31, 32, 1024, 1048500] as $length) {
            $run = str_repeat('a', $length);
            foreach (['"'.$run.'"', '"'.$run.'\\"end"', '"'.$run.'\\\\"', '"'.$run.'\\', '"'.$run] as $raw) { $cases[] = $raw; }
        }
        $cases[] = str_repeat('[', 32).'"x"'.str_repeat(']', 32);
        $cases[] = str_repeat('[', 33).'"x"'.str_repeat(']', 33);
        $cases[] = '"'.str_repeat('x', 1048576).'"';
        foreach ($cases as $index => $raw) {
            $expected = $this->outcome(EntryStrictJsonScanner::class, $raw);
            self::assertSame($expected, $this->outcome(StrictJson::class, $raw), 'Case '.$index);
            StrictJson::within(function () use ($expected, $raw, $index): void {
                self::assertSame($expected, $this->outcome(StrictJson::class, $raw), 'Scoped case '.$index);
                self::assertSame($expected, $this->outcome(StrictJson::class, $raw), 'Repeated case '.$index);
            });
        }
    }
}
