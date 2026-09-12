<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\DeepSeek;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules as R;

/** Exact verified tariff projection; no retained public price snapshot is a default. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class Tariff
{
    public function __construct(public string $account, public string $model,
        public int $notBefore, public int $expiresAt, public int $missRate,
        public int $hitRate, public int $outputRate, public string $rounding,
        public int $extraFee)
    {
        R::text($account); R::require(in_array($model, Wire::MODELS, true), 'MODEL');
        R::time($notBefore); R::time($expiresAt);
        foreach ([$missRate,$hitRate,$outputRate,$extraFee] as $n) { R::integer($n); }
        R::require($expiresAt > $notBefore && $rounding === 'ceil_each_meter_microusd'
            && $extraFee === 0 && $hitRate <= $missRate, 'TARIFF_EVIDENCE');
    }
    public function current(int $now, int $expiry, string $model): void
    {
        R::require($model === $this->model && $now >= $this->notBefore && $now < $this->expiresAt
            && $expiry <= $this->expiresAt && $expiry > $now, 'TARIFF_SCOPE');
    }
    public function cost(int $hit, int $miss, int $output): int
    {
        $total = 0;
        foreach ([[$hit,$this->hitRate],[$miss,$this->missRate],[$output,$this->outputRate]] as [$tokens,$rate]) {
            R::integer($tokens);
            R::require($rate === 0 || $tokens <= intdiv(PHP_INT_MAX, $rate), 'COST_OVERFLOW');
            $product = $tokens * $rate;
            $meter = intdiv($product, 1000000) + ($product % 1000000 === 0 ? 0 : 1);
            R::require($meter <= PHP_INT_MAX - $total, 'COST_OVERFLOW'); $total += $meter;
        }
        return $total;
    }
}
