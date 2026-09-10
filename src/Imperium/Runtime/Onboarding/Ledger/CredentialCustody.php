<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Ledger;
interface CredentialCustody { public function issue(array $claim,array $operation): object; public function consume(object $capability,array $claim,array $operation,callable $delivery): void; }
