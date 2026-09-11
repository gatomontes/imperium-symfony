<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Ledger;
interface PreparedOperation { public function prepare(array $terms): array; }
