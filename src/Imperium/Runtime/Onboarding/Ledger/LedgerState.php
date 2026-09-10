<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Ledger;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules as R;
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class LedgerState
{
    public static function key(string $tag,array $tuple): string { return R::hash([$tag,...$tuple]); }
    public static function h(array $h,string $name): array { R::record($h);R::require($h['schema']==='imperium.bootstrap-'.$name.'/v1','LEDGER_SCHEMA');return $h; }
    public static function validate(array $s): void {
        self::h($s['migration'],'state-migration');
        R::object($s['migration']['body'],['from_schema','to_schema','predecessor_head','prior_subtree_digest','preserved_maps_digest']);
        R::require($s['migration']['body']['from_schema']==='imperium.onboarding-authority-state/v1' && $s['migration']['body']['to_schema']===$s['schema'],'STATE_MIGRATION');
        $count=0;$records=[];$walk=static function(mixed $v)use(&$walk,&$records):void{if(!is_array($v)){return;}if(isset($v['schema'],$v['record_digest'])){$records[$v['schema'].':'.$v['record_digest']]=true;}foreach($v as $child){$walk($child);}};
        foreach(StateMigration::MAPS as $map){R::require(isset($s[$map]) && is_array($s[$map]),'STATE_MAP');$count+=count($s[$map]);$walk($s[$map]);}
        R::require($count<=8192 && count($records)<=8192 && count($s['commands'])<=4096 && count($s['sequences'])<=256,'LIMIT_EXCEEDED');
        foreach(['bindings','applications','assessment_views'] as $map){R::require($s[$map]===[],'UNSUPPORTED_STATE_PRODUCER');}
        foreach($s['commands'] as $k=>$c){
            R::object($c,['key','request','raw_digest','result','ref']);R::digest($c['raw_digest']);
            R::require($k===self::key('command',$c['key']) && count($c['key'])===3,'COMMAND_KEY');
            R::require(R::same($c['key'],[$s['trust']['instance_id'],$c['request']['sequence_id'],$c['request']['command_id']]),'COMMAND_KEY');
            R::require($c['ref']['request_digest']===R::hash($c['request']) && $c['ref']['result_digest']===R::hash($c['result']),'COMMAND_DIGEST');
            R::object($c['ref'],['sequence_id','command_id','request_digest','result_digest']);
            R::require($c['ref']['sequence_id']===$c['request']['sequence_id'] && $c['ref']['command_id']===$c['request']['command_id'] && $c['result']['sequence_id']===$c['ref']['sequence_id'] && $c['result']['command_id']===$c['ref']['command_id'] && $c['result']['request_digest']===$c['ref']['request_digest'],'COMMAND_IDENTITY');
            R::object($c['result'],['sequence_id','command_id','request_digest','step_id','predecessor_ref','policy_ref','budget_ref','observed_head','admission_status']);
        }
        foreach($s['sequences'] as $k=>$v){
            R::object($v,['key','registration','head']);R::require($k===self::key('sequence',$v['key']),'SEQUENCE_KEY');self::h($v['registration'],'sequence-registration');
            R::require(R::same($v['registration']['body']['sequence_ref'],['instance_id'=>$v['key'][0],'sequence_id'=>$v['key'][1]]),'SEQUENCE_REF');self::command($s,$v['head']);
        }
        foreach($s['steps'] as $k=>$v){
            R::object($v,['key','consumption','completion']);R::require($k===self::key('step',$v['key']),'STEP_KEY');$h=self::h($v['consumption'],'step-consumption');
            $b=R::object($h['body'],['sequence_ref','command_ref','policy_ref','step_id','slot_id','authority_consumption','prepared_operation_ref','predecessor_head']);
            R::require(R::same($v['key'],[$h['instance_id'],$b['policy_ref']['digest'],$b['step_id']]),'STEP_KEY');$command=self::command($s,$b['command_ref']);R::require($command['result']['step_id']===$b['step_id'] && R::same($command['result']['policy_ref'],$b['policy_ref']),'STEP_COMMAND');
            if($v['completion']!==null){$c=self::h($v['completion'],'step-completion');R::object($c['body'],['command_ref','step_consumption_ref','effect','result_refs','completed_at']);R::require(R::same($c['body']['command_ref'],$b['command_ref']) && R::same($c['body']['step_consumption_ref'],R::reference($h)),'COMPLETION_LINK');}
        }
        $used=[];
        foreach($s['slots'] as $k=>$v){
            R::object($v,['key','command_ref','authority_key']);R::require($k===self::key('slot',$v['key']),'SLOT_KEY');self::command($s,$v['command_ref']);$a=R::hash($v['authority_key']);R::require(!isset($used[$a]),'AUTHORITY_ALREADY_CONSUMED');$used[$a]=true;
        }
        foreach($s['claims'] as $k=>$v){
            R::object($v,['key','record','operation','maximum','settled','custody']);R::require($k===self::key('claim',$v['key']),'CLAIM_KEY');self::h($v['record'],'cognition-claim');
            R::object($v['record']['body'],['sequence_ref','command_ref','policy_ref','authority_source','prepared_operation_ref','budget_ref','authority_consumption','lease_consumption','custody_checkpoint','response_ref']);
            self::command($s,$v['record']['body']['command_ref']);
            $operation=self::h($v['operation']['record'],'prepared-operation-record');R::require(R::same($operation['body'],['operation'=>$v['operation']['prepared']]) && R::same(R::reference($operation),$v['record']['body']['prepared_operation_ref']) && R::same($v['maximum'],$v['operation']['prepared']['maximum']),'CLAIM_OPERATION');
            R::require($v['record']['body']['response_ref']===null,'IMMUTABLE_CLAIM');
            $prior=null;$stages=CustodyCoordinator::STAGES;R::require(count($v['custody'])<=count($stages),'CUSTODY_CHAIN');
            foreach($v['custody'] as $i=>$c){self::h($c,'custody-checkpoint');$b=R::object($c['body'],['claim_ref','operation_digest','stage','previous_ref','response_metadata','response_envelope']);R::require($b['stage']===$stages[$i] && R::same($b['previous_ref'],$prior) && R::same($b['claim_ref'],R::reference($v['record'])) && $b['operation_digest']===R::hash($v['operation']),'CUSTODY_CHAIN');$prior=R::reference($c);}
        }
        foreach($s['budget_bindings'] as $k=>$v){R::object($v,['key','record']);R::require($k===self::key('budget',$v['key']),'BUDGET_KEY');self::h($v['record'],'budget-binding');}
        foreach($s['source_fences'] as $v){self::h($v,'source-fence');}
        foreach($s['group_inputs'] as $h){self::h($h,'group-input');R::object($h['body'],['policy_ref','group_id','holder_ref','configuration_ref','workload_ref','resolved_input_refs','semantic_input_digest']);}
        foreach($s['attempt_outcomes'] as $o){R::object($o,['schema','id','instance_id','sequence_ref','group_id','attempt_step_id','claim_ref','response_refs','classification','reason_code','evidence_refs','usage_ref','retry_policy_ref','observed_at','record_digest']);$plain=$o;unset($plain['record_digest']);R::require($o['schema']==='imperium.bootstrap-attempt-outcome/v1' && R::hash($plain)===$o['record_digest'] && in_array($o['classification'],['SUCCEEDED','TERMINAL_FAILURE'],true),'OUTCOME_SCHEMA');}
    }
    public static function command(array $s,array $ref): array {
        R::object($ref,['sequence_id','command_id','request_digest','result_digest']);
        $c=$s['commands'][self::key('command',[$s['trust']['instance_id'],$ref['sequence_id'],$ref['command_id']])]??null;
        R::require(is_array($c) && R::same($c['ref'],$ref),'COMMAND_ORIGINAL_MISSING');return $c;
    }
    public static function step(array $s,array $policy,string $id): array {
        $v=$s['steps'][self::key('step',[$policy['instance_id'],$policy['record_digest'],$id])]??null;
        R::require(is_array($v) && $v['completion']!==null,'STEP_NOT_READY');return $v;
    }
}
