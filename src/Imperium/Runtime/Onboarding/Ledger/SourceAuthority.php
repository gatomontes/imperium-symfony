<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Ledger;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\AuthorityStore;
interface SourceAuthority { public function verify(AuthorityStore $store,array $state,array $policy,string $effect,array $terms,array $operation): void; public function validateResponse(AuthorityStore $store,array $state,array $policy,string $effect,array $operation,array $envelope): string; public function select(AuthorityStore $store,array $state,array $policy,array $step): array; }
