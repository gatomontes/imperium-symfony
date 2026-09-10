<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\AuthorityAdmission;

/** Read-only observations. No result is an execution/consumption capability. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class Resolver
{
    public function __construct(private AuthorityStore $store) {}
    public function original(array $ref): array {
        return $this->store->journal->inspect(function(array $frame)use($ref):array {
            $s=$this->store->state($frame['state']); $this->store->currentTrust($s);
            return $this->store->checkSource($s,$ref);
        });
    }
    public function current(array $authority,string $effect,array $termsRef): array {
        return $this->store->journal->inspect(function(array $frame)use($authority,$effect,$termsRef):array {
            $s=$this->store->state($frame['state']);
            $facts=CurrentAuthority::verify($this->store,$s,$authority,$effect,$termsRef);
            Rules::require($facts['obligations']===[],'DYNAMIC_PREREQUISITES_MISSING');
            return ['status'=>'CURRENT_STATIC_AUTHORITY_PENDING_EXECUTION','observed_head'=>['generation'=>$frame['generation'],'digest'=>$frame['record_digest']],
                'effect'=>$effect,'terms_ref'=>$termsRef,'execution_authority'=>false,'authority_consumed'=>false,'effect_completed'=>false];
        });
    }
}
