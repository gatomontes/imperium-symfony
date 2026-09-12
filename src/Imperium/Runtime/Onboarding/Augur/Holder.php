<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Augur;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules as R,Policy,AuthorityStore};
use App\Imperium\Runtime\Onboarding\Ledger\LedgerState;
use App\Imperium\Runtime\Onboarding\BaseSelection\{BaseInput,BaseSelector};

/** Structural history proof, distinct from current constitutional/factual authentication. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class Holder
{
    public const FIELDS=['policy_ref','command_ref','terms_ref','constitution_ref','root_identity','seat','generation','binding','profile_ref','persona_ref','charter_ref','base_completion_ref','base_proposal','expires_at'];
    public static function validate(array $s,callable $load):void
    {
        R::require(count($s['bindings'])<=1,'ROOT_ALREADY_CONSUMED');$owned=[];
        foreach($s['bindings'] as $key=>$entry){
            R::object($entry,['key','record']);$h=R::record($entry['record']);$b=R::object($h['body'],self::FIELDS);
            R::require($h['schema']==='imperium.bootstrap-augur-holder/v1' && $h['producer']['service']==='onboarding.authority-admission','HOLDER_SCHEMA');
            R::digest($b['root_identity']);R::require(R::same($entry['key'],[$b['root_identity']]) && $key===LedgerState::key('binding',$entry['key']),'HOLDER_KEY');
            $policy=$load($b['policy_ref']);$command=LedgerState::command($s,$b['command_ref']);
            R::require(R::same($command['result']['policy_ref'],$b['policy_ref']) && $command['result']['step_id']==='found-augur','HOLDER_COMMAND');
            $step=LedgerState::step($s,$policy,'found-augur');
            R::require(R::same($step['consumption']['body']['command_ref'],$command['ref']) && R::same($step['completion']['body']['result_refs'],[R::reference($h)])
                && $step['completion']['body']['completed_at']===$h['created_at'] && $b['generation']===$command['result']['observed_head']['generation']+1,'HOLDER_PUBLICATION');
            $slots=array_values(array_filter($policy['body']['effect_slots'],static fn(array $v):bool=>$v['slot_id']==='slot.found-augur'));
            R::require(count($slots)===1,'HOLDER_SLOT');$derived=FoundingRule::derive($s,$policy,$slots[0],$load);
            R::require(R::same($derived['terms_ref'],$b['terms_ref']),'HOLDER_TERMS');
            $terms=$load($b['terms_ref']);$intent=Policy::content($load($terms['body']['terms']),'augur-founding-intent');
            R::require(R::same($intent,['constitution_ref'=>$b['constitution_ref'],'binding_ref'=>$b['binding']['binding_ref']]),'HOLDER_INTENT');
            $constitution=Policy::content($load($b['constitution_ref']),'augur-constitution');
            R::object($constitution,['schema','instance_id','operator_id','root_identity','seat','charter_ref','persona_ref','profile_ref','not_before','expires_at']);
            R::require($constitution['schema']==='imperium.augur-constitution/v1' && $constitution['instance_id']===$h['instance_id'] && $constitution['operator_id']===$s['trust']['body']['issuer']['id']
                && $constitution['root_identity']===$b['root_identity'] && $constitution['seat']==='oracle.augur' && $b['seat']==='oracle.augur','HOLDER_CONSTITUTION');
            foreach(['profile_ref','persona_ref','charter_ref'] as $field){R::require(R::same($b[$field],$constitution[$field]),'HOLDER_ARTIFACT');$load($b[$field]);}
            R::time($constitution['not_before']);R::time($constitution['expires_at']);R::time($b['expires_at']);
            R::require($constitution['not_before']<=$h['created_at'] && $h['created_at']<$b['expires_at'] && $b['expires_at']===min($constitution['expires_at'],$policy['body']['expires_at'],$slots[0]['expires_at']),'HOLDER_TIME');
            $base=LedgerState::step($s,$policy,'select-base');R::require(R::same($b['base_completion_ref'],R::reference($base['completion'])),'HOLDER_BASE_COMPLETION');
            R::require(strlen(\App\Bootstrap\CanonicalJson::encode($b['base_proposal']))<=1048576,'HOLDER_PROPOSAL_LIMIT');
            $proposal=(new BaseSelector())->propose(BaseInput::fromArray($b['base_proposal']));
            R::require($proposal->status==='PROPOSED_BASE' && R::same($proposal->proposedBinding,$b['binding']) && R::same($proposal->input->context['policy_ref'],$b['policy_ref'])
                && R::same($proposal->input->context['profile_ref'],$b['profile_ref']) && $proposal->input->context['evaluated_at']===$base['completion']['body']['completed_at']*1000,'HOLDER_BASE_PROPOSAL');
            R::require(R::same(LedgerState::step($s,$policy,'map-base')['completion']['body']['result_refs'],[$b['binding']['mapping_ref']]),'HOLDER_MAPPING_COMPLETION');
            R::require(R::same($h['sources'],self::sources($b,$terms)),'HOLDER_SOURCES');foreach($h['sources'] as $ref){$load($ref);}
            $owned[R::key(R::reference($h))]=true;
        }
        foreach($s['steps'] as $step){if(($step['completion']['body']['effect']??null)==='CONSTITUTE_FOUNDING_AUGUR'){
            R::require(count($step['completion']['body']['result_refs'])===1 && isset($owned[R::key($step['completion']['body']['result_refs'][0])]),'HOLDER_MISSING');
        }}
    }
    public static function sources(array $b,array $terms):array
    {
        return R::refs([$b['policy_ref'],$b['terms_ref'],$terms['body']['terms'],$b['constitution_ref'],$b['profile_ref'],$b['persona_ref'],$b['charter_ref'],
            ...array_map(static fn(string $k):array=>$b['binding'][$k],['binding_ref','configuration_ref','adapter_ref','mapping_ref'])]);
    }
    public static function current(AuthorityStore $store,array $s,array $ref):array
    {
        self::validate($s,fn(array $r):array=>$store->lookup($s,$r));$matches=[];
        foreach($s['bindings'] as $entry){if(R::same(R::reference($entry['record']),$ref)){$matches[]=$entry['record'];}}
        R::require(count($matches)===1,'HOLDER_ORIGINAL_MISSING');$h=$matches[0];$store->currentTrust($s);
        R::require($h['created_at']<=$store->now() && $store->now()<$h['body']['expires_at'],'HOLDER_CURRENT');
        foreach($h['sources'] as $source){$store->checkSource($s,$source);}return $h;
    }
}
