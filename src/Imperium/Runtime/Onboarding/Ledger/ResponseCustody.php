<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Ledger;
interface ResponseCustody { public function dispatch(array $operation,#[\SensitiveParameter] mixed $authentication): array; public function retain(array $envelope): void; public function read(array $claimRef): array; }
