<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\Formation;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules as R;
use App\Imperium\Runtime\Onboarding\Ledger\LedgerState;
/** Pure cross-type calculation over the sole journal. No balance cache or new permissions. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class SharedExposure
{
    public static function meters(array $m,bool $call=true): void {
        R::object($m,SessionExposure::FIELDS);
        foreach($m as $v){R::require(is_int($v) && $v>=0 && $v<=1000000000,'RESOURCE_METER');}
        R::require(!$call || $m['calls']===1,'RESOURCE_CALL');
    }
    public static function formation(array $state,string $sessionId,array $maximum,?string $ownAttempt,FormationJournal $journal,\App\Imperium\Runtime\Clock $clock): void {
        if(($state['onboarding']['schema']??null)!=='imperium.onboarding-authority-state/v2'){return;}
        LedgerState::validate($state['onboarding']);$found=[];
        foreach($state['onboarding']['budget_bindings'] as $v){foreach($v['record']['body']['source_bindings'] as $binding){
            if($binding['kind']==='formation' && $binding['session_id']===$sessionId){
                R::require($binding['resource_decision_digest']===FormationJournal::digest($state['sessions'][$sessionId]['provider_resource_decision']),'BUDGET_RESOURCE_IDENTITY');$found[$v['record']['body']['budget_identity']]=$v['record']['body'];
            }
        }}
        R::require(count($found)===1,'SHARED_BUDGET_SOURCE_UNRESOLVED');$body=array_values($found)[0];
        $s=$state['onboarding'];$trust=$s['trust'];
        $authority=new \App\Imperium\Runtime\Onboarding\AuthorityAdmission\AuthorityStore($journal,$clock,$trust['instance_id'],$trust['citadel_id'],$trust['body']['issuer']['id'],$s['migration']['producer']['source_commit']);
        $authority->currentTrust($s);foreach($s['budget_bindings'] as $bindingRecord){if($bindingRecord['record']['body']['budget_identity']===$body['budget_identity']){foreach($bindingRecord['record']['body']['source_bindings'] as $source){if($source['kind']==='onboarding'){$authority->checkSource($s,$source['policy_ref']);}}}}$authority->checkSource($s,$body['budget_ref']);
        $root=$authority->checkSource($s,$body['limit_ref']);$limits=json_decode($root['body']['content'],true,512,JSON_THROW_ON_ERROR)['limits'];
        self::check($state,$body['budget_identity'],$limits,$maximum,$ownAttempt===null?null:['formation',$sessionId,$ownAttempt]);
    }
    public static function check(array $state,string $identity,array $limits,array $maximum,?array $own=null): void {
        self::meters($limits,false);self::meters($maximum);$s=$state['onboarding'];$members=[];
        foreach($s['budget_bindings'] as $v){
            $b=$v['record']['body'];if($b['budget_identity']!==$identity){continue;}
            foreach($b['source_bindings'] as $source){if($source['kind']==='formation'){$members[$source['session_id']]=$source['resource_decision_digest'];}}
        }
        $used=array_fill_keys(SessionExposure::FIELDS,0);$pending=0;
        $add=static function(array $max,?array $settled)use(&$used,&$pending):void {
            self::meters($max);if($settled!==null){self::meters($settled);foreach($settled as $f=>$n){R::require($n<=$max[$f],'USAGE_UNTRUSTWORTHY');}}else{++$pending;}
            foreach($used as $f=>$n){$v=($settled??$max)[$f];R::require($n<=PHP_INT_MAX-$v,'RESOURCE_OVERFLOW');$used[$f]+=$v;}
        };
        foreach($state['sessions']??[] as $sid=>$session){
            if(!isset($members[$sid])){R::require(($session['attempts']??[])===[],'SHARED_BUDGET_SOURCE_UNRESOLVED');continue;}
            R::require($members[$sid]===FormationJournal::digest($session['provider_resource_decision']),'BUDGET_RESOURCE_IDENTITY');
            foreach($session['attempts'] as $aid=>$attempt){if($own===['formation',$sid,$aid]){continue;}$add($attempt['maximum'],$attempt['settled']);}
        }
        foreach($s['claims'] as $id=>$claim){
            if($own===['onboarding',$id]){continue;}
            $policy=$claim['record']['body']['policy_ref'];$belongs=false;
            foreach($s['budget_bindings'] as $v){$b=$v['record']['body'];if($b['budget_identity']!==$identity){continue;}foreach($b['source_bindings'] as $src){if($src['kind']==='onboarding' && R::same($src['policy_ref'],$policy)){$belongs=true;}}}
            if($belongs){$add($claim['maximum'],$claim['settled']);}
            elseif($claim['settled']===null){throw new \RuntimeException('O2_SHARED_BUDGET_SOURCE_UNRESOLVED');}
        }
        foreach($s['source_fences'] as $f){if($f['body']['budget_identity']===$identity){R::require($own!==null && ($own[0]??null)==='onboarding' && R::same($f['body']['claim_ref'],R::reference($s['claims'][$own[1]]['record'])),'OUTCOME_UNKNOWN');}}
        R::require($pending===0,'SHARED_CONCURRENCY');
        foreach($used as $f=>$n){R::require($n<=$limits[$f] && $maximum[$f]<=$limits[$f]-$n,'SHARED_BUDGET_EXHAUSTED');}
    }
}
