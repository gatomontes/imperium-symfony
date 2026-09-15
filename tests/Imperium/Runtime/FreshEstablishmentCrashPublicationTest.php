<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\FreshEstablishmentCrashCase;
use PHPUnit\Framework\Attributes\DataProvider;
final class FreshEstablishmentCrashPublicationTest extends FreshEstablishmentCrashCase
{
    public static function points(): iterable {
        foreach (['before-journal-rename', 'after-journal-rename'] as $point) { yield 'reserve-'.$point => ['reserve', $point]; }
        foreach (['before-write:intent', 'after-write:intent', 'before-package-completion', 'after-package-completion', 'before-journal-rename', 'after-journal-rename'] as $point) { yield $point => ['complete', $point]; }
    }
    #[DataProvider('points')]
    public function testActualProcessDeathAndExactForwardRecovery(string $operation, string $point): void { $this->proveCrash($operation, $point); }
}
