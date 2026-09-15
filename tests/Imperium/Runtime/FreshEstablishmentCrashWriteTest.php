<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\FreshEstablishmentCrashCase;
use PHPUnit\Framework\Attributes\DataProvider;
final class FreshEstablishmentCrashWriteTest extends FreshEstablishmentCrashCase
{
    public static function points(): iterable { for ($i = 0; $i < 18; ++$i) { foreach (['before-write', 'after-write'] as $point) { yield $point.'-'.$i => [$point, $i]; } } }
    #[DataProvider('points')]
    public function testEveryNativeTemporaryWriteCrash(string $point, int $index): void { $this->proveCrash('complete', $point, $index); }
}
