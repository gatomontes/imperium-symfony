<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;
final class FreshEstablishmentClock implements \App\Imperium\Runtime\Clock
{
    public int $at = 1800000000;
    public function now(): \DateTimeImmutable { return new \DateTimeImmutable('@'.$this->at); }
}
