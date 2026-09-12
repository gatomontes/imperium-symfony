<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Augur;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules as R,Policy,Admission};
use App\Imperium\Runtime\Onboarding\Ledger\LedgerState;

/** Exact finite derivation, not readiness or permission to publish a holder. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class FoundingRule
{
    public static function derive(array $s,array $policy,array $slot,callable $load):array
    {
        R::require(in_array($s['schema'],['imperium.onboarding-authority-state/v3','imperium.onboarding-authority-state/v4'],true)
            && $slot['effect']==='CONSTITUTE_FOUNDING_AUGUR' && $slot['slot_id']==='slot.found-augur'
            && $slot['terms_rule']['kind']==='eligible_binding' && $slot['terms_rule']['base_step_id']==='select-base','DYNAMIC_PREREQUISITES_MISSING');
        $base=LedgerState::step($s,$policy,'select-base');
        $step=array_values(array_filter($policy['body']['steps'],static fn(array $v):bool=>$v['step_id']==='select-base'))[0];
        $manifest=$step['input_refs'][1]['ref'];$results=$base['completion']['body']['result_refs'];$selected=[];
        foreach($policy['body']['candidate_bindings'] as $binding){
            if(R::same($results,R::refs([$manifest,$binding['binding_ref']]))){$selected[]=$binding;}
        }
        R::require(count($selected)===1,'BASE_COMPLETION_SCOPE');$found=[];
        foreach($slot['terms_rule']['permitted_object_refs'] as $ref){
            $terms=$load($ref);Admission::terms($terms,'CONSTITUTE_FOUNDING_AUGUR');
            $intent=Policy::content($load($terms['body']['terms']),'augur-founding-intent');R::object($intent,['constitution_ref','binding_ref']);
            $load(R::ref($intent['constitution_ref']));
            if(R::same($intent['binding_ref'],$selected[0]['binding_ref'])){$found[]=$ref;}
        }
        R::require(count($found)===1,'FOUNDING_PERMITTED_TUPLE');
        return ['terms_ref'=>$found[0],'derivation_input_refs'=>R::refs([R::reference($base['completion']),$manifest,$selected[0]['binding_ref']])];
    }
}
