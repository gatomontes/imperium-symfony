<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Onboarding\BaseSelection;

/** Nonnegative signed-64-bit arithmetic, including intermediates. Never falls back to float. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class ExactCost
{
    public static function add(mixed $a, mixed $b): int
    {
        Boundary::integer($a); Boundary::integer($b);
        if ($b > PHP_INT_MAX - $a) { throw new \OverflowException('BASE_ARITHMETIC_OVERFLOW'); }
        return $a + $b;
    }

    public static function multiply(mixed $a, mixed $b): int
    {
        Boundary::integer($a); Boundary::integer($b);
        if ($a !== 0 && $b > intdiv(PHP_INT_MAX, $a)) { throw new \OverflowException('BASE_ARITHMETIC_OVERFLOW'); }
        return $a * $b;
    }

    public static function meter(mixed $quantity, mixed $numerator, mixed $denominator): int
    {
        Boundary::integer($denominator, true);
        $product = self::multiply($quantity, $numerator);
        // Avoid the overflowing (product + denominator - 1) idiom.
        return self::add(intdiv($product, $denominator), $product % $denominator === 0 ? 0 : 1);
    }
}
