<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Ledger;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore,Rules as R};
/** Finite group interpreter; all successes resolve original linked claims, never supplied booleans. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class AssessmentGroups
{
    public static function group(array $policy,string $id):array{$rows=array_values(array_filter($policy['body']['assessment_groups'],static fn(array $g):bool=>$g['group_id']===$id));R::require(count($rows)===1,'GROUP_MISSING');return $rows[0];}
    public static function outcome(array $s,array $policy,string $step):?array{
        $matches=[];foreach($s['attempt_outcomes'] as $o){if($o['attempt_step_id']===$step && $o['sequence_ref']['policy_digest']===$policy['record_digest']){$matches[]=$o;}}
        R::require(count($matches)<=1,'OUTCOME_CONFLICT');if($matches===[]){return null;}$o=$matches[0];$claim=null;
        foreach($s['claims'] as $c){if(R::same(R::reference($c['record']),$o['claim_ref'])){$claim=$c;}}
        R::require(is_array($claim) && $claim['record']['body']['authority_source']['kind']==='assessment' && R::same($claim['record']['body']['policy_ref'],R::reference($policy)) && count($claim['custody'])===5 && $claim['settled']!==null,'OUTCOME_ORIGINAL_MISSING');
        $command=LedgerState::command($s,$claim['record']['body']['command_ref']);R::require($command['result']['step_id']===$step,'OUTCOME_STEP');return $o;
    }
    public static function success(array $s,array $policy,string $group):array{
        $g=self::group($policy,$group);$success=null;$earlierUnresolved=false;
        foreach($g['attempt_step_ids'] as $id){$o=self::outcome($s,$policy,$id);if($o===null){if($success===null){$earlierUnresolved=true;}continue;}
            if($o['classification']==='SUCCEEDED'){R::require($success===null && !$earlierUnresolved,'GROUP_LINEAGE');$success=$o;}
            else{R::require($success===null,'GROUP_ALREADY_SUCCEEDED');}
        }
        R::require($success!==null,'GROUP_NOT_READY');return $success;
    }
    public static function ready(array $s,array $policy,array $step):void{
        $condition=$step['run_condition'];if($condition['kind']==='after_success'){return;}
        R::require(in_array($condition['kind'],['initial_assessment','retry_after_confirmed_failure'],true),'RUN_CONDITION');$g=self::group($policy,$condition['group_id']);
        foreach($g['requires_success_of'] as $prior){self::success($s,$policy,$prior);}
        $index=array_search($step['step_id'],$g['attempt_step_ids'],true);R::require($index!==false,'GROUP_STEP');
        foreach($g['attempt_step_ids'] as $id){$o=self::outcome($s,$policy,$id);R::require($o===null || $o['classification']!=='SUCCEEDED','GROUP_ALREADY_SUCCEEDED');}
        if($condition['kind']==='initial_assessment'){R::require($index===0,'GROUP_INITIAL');}
        else{
            R::require($index>0 && $condition['prior_attempt_step_id']===$g['attempt_step_ids'][$index-1],'RETRY_PREDECESSOR');
            // The selected provider's actual supported safe-retry allowlist is empty.
            R::require($policy['body']['evidence_policy']['retryable_failure_allowlist']===[],'RETRY_POLICY_CHANGED');throw new \RuntimeException('O2_RETRY_REASON_NOT_ADMITTED');
        }
    }
    public static function freeze(AuthorityStore $store,array &$s,array $policy,array $step,array $operation):void{
        $g=self::group($policy,$step['run_condition']['group_id']);$refs=[];
        foreach($step['input_refs'] as $selector){if($selector['kind']==='public_ref'){$h=$store->checkSource($s,$selector['ref']);$refs[]=R::reference($h);}else{$o=self::success($s,$policy,$selector['group_id']);$refs[]=['schema'=>$o['schema'],'id'=>$o['id'],'digest'=>$o['record_digest']];}}
        $body=['policy_ref'=>R::reference($policy),'group_id'=>$g['group_id'],'holder_ref'=>$operation['authority_source']['holder_ref'],'configuration_ref'=>$operation['configuration_ref'],'workload_ref'=>$g['workload_ref'],'resolved_input_refs'=>$refs,'semantic_input_digest'=>R::hash([$refs,$operation['wire'],$operation['model'],$operation['configuration_ref'],$operation['authority_source']['holder_ref']])];
        $key=LedgerState::key('group',[$store->instance,$policy['record_digest'],$g['group_id']]);
        if(isset($s['group_inputs'][$key])){R::require(R::same($s['group_inputs'][$key]['body'],$body),'GROUP_INPUT_CHANGED');return;}
        R::require($step['step_id']===$g['attempt_step_ids'][0],'GROUP_INPUT_MISSING');$s['group_inputs'][$key]=$store->make('imperium.bootstrap-group-input/v1','group-'.substr($key,7,24),$body);
    }
    public static function recordOutcome(AuthorityStore $store,array $s,array $claim,string $classification):array{
        R::require(in_array($classification,['SUCCEEDED','TERMINAL_FAILURE'],true),'RETRY_REASON_NOT_ADMITTED');$command=LedgerState::command($s,$claim['record']['body']['command_ref']);$policyRef=$claim['record']['body']['policy_ref'];
        $policy=$store->checkSource($s,$policyRef);$step=array_values(array_filter($policy['body']['steps'],static fn(array $v):bool=>$v['step_id']===$command['result']['step_id']))[0];$last=$claim['custody'][4];
        $o=['schema'=>'imperium.bootstrap-attempt-outcome/v1','id'=>'outcome-'.substr(R::hash($claim['record']),7,24),'instance_id'=>$store->instance,'sequence_ref'=>$claim['record']['body']['sequence_ref'],'group_id'=>$step['run_condition']['group_id'],'attempt_step_id'=>$step['step_id'],'claim_ref'=>R::reference($claim['record']),'response_refs'=>[R::reference($last)],'classification'=>$classification,'reason_code'=>$classification==='SUCCEEDED'?'VALIDATED_RESULT':'FINAL_NONCONFORMING_RESULT','evidence_refs'=>[R::reference($last)],'usage_ref'=>R::reference($last),'retry_policy_ref'=>$policyRef,'observed_at'=>$store->now()];$o['record_digest']=R::hash($o);return $o;
    }
}
