<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use PHPUnit\Framework\TestCase;

final class CanonicalTraversalCompatibilityTest extends TestCase
{
    // Entry implementation retained as a differential oracle for this pure operation.
    private static function originalSort(mixed $value): mixed
    {
        if (!is_array($value)) { return $value; }
        if (!array_is_list($value)) { ksort($value, SORT_STRING); }
        foreach ($value as $key => $item) { $value[$key] = self::originalSort($item); }
        return $value;
    }
    private static function original(mixed $value): string
    {
        return json_encode(self::originalSort($value), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    public function testIdenticalCanonicalBytesForTypedNestedValues(): void
    {
        $cases = [null, false, true, 0, -1, PHP_INT_MAX, 1.0, -0.0, 1.25, '', 'é/漢字', [],
            ['z'=>1, 'a'=>['z'=>2, 'b'=>[false, null, 'text']]], [10=>'ten', 2=>'two', -1=>'negative'],
            ['01'=>'string key', '0'=>'integer key'], (object)['z'=>2, 'a'=>1]];
        foreach ($cases as $value) {
            self::assertSame(self::original($value), CanonicalJson::encode($value));
            self::assertSame(self::original(['z'=>$value, 'a'=>[$value]]), CanonicalJson::encode(['z'=>$value, 'a'=>[$value]]));
        }
        foreach (["\xFF", INF, NAN, ['nested'=>"\xFF"]] as $value) {
            try { self::original($value); self::fail('Oracle should reject'); }
            catch (\JsonException $expected) {
                try { CanonicalJson::encode($value); self::fail('Encoder should reject'); }
                catch (\JsonException $actual) { self::assertSame($expected->getCode(), $actual->getCode()); }
            }
        }
    }
    public function testObjectsAndArrayAliasesKeepEntrySemantics(): void
    {
        $make = static fn() => new class implements \JsonSerializable {
            public int $calls = 0;
            public function jsonSerialize(): mixed { return ['z'=>++$this->calls, 'a'=>1]; }
        };
        $left=$make(); $right=$make();
        self::assertSame(self::original(['z'=>$left, 'a'=>$left]), CanonicalJson::encode(['z'=>$right, 'a'=>$right]));
        self::assertSame(2, $right->calls);
        $scalarA='text'; $scalarB='text'; $arrayA=['z'=>2, 'a'=>1]; $arrayB=$arrayA;
        $a=['scalar'=>&$scalarA, 'array'=>&$arrayA]; $b=['scalar'=>&$scalarB, 'array'=>&$arrayB];
        self::assertSame(self::original($a), CanonicalJson::encode($b));
        self::assertSame($arrayA, $arrayB);
        self::assertSame($scalarA, $scalarB);
    }
}
