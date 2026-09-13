<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Console;

interface Gateway
{
    public function onboard(string $request): array;
    public function status(string $sequence): array;
    public function resume(string $request): array;
}
