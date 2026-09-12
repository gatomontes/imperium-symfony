<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Assignment;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules as R,Policy,Admission,Act};
use App\Imperium\Runtime\Onboarding\Ledger\{LedgerState,CommandLedger};
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class ApplicationHistory
{
    public const FIELDS=['key','receipt','command_ref','previous_application_ref','terms_ref','selected_set_ref','authority_key'];
    public static function ordered(array $s):array
    {
        $entries=array_values($s['applications']);
        usort($entries,static fn(array $a,array $b):int=>$a['receipt']['body']['commit_head']['generation']<=>$b['receipt']['body']['commit_head']['generation']);return $entries;
    }
    public static function latest(array $s):?array {$entries=self::ordered($s);return $entries===[]?null:$entries[array_key_last($entries)];}
    public static function next(array $set,array $prior):array
    {
        $out=[];foreach($set['assignments'] as $i=>$row){R::require($prior[$i]['generation']<PHP_INT_MAX,'ASSIGNMENT_GENERATION_LIMIT');$out[]=[...$row,'generation'=>$prior[$i]['generation']+1];}return $out;
    }
    public static function eligible(array $set,array $originals):void
    {
        foreach($set['assignments'] as $i=>$tuple){
            foreach([0,$i+1] as $g){$found=array_values(array_filter($originals['groups'][$g]['response']->candidateRows,static fn($row):bool=>$row->bindingRef->digest===$tuple['binding_ref']['digest']));
                R::require(count($found)===1 && $found[0]->fit==='FIT','CHANGE_ASSESSMENT_SCOPE');}
        }
    }
    public static function replacementSlots(array $s):array
    {
        $out=[];
        foreach($s['applications'] as $a){if($a['previous_application_ref']===null){continue;}
            $b=$a['receipt']['body'];$tuple=[$s['trust']['instance_id'],$b['policy_ref']['digest'],'slot.change-'.$a['authority_key'][2]];
            $out[LedgerState::key('slot',$tuple)]=['key'=>$tuple,'command_ref'=>$a['command_ref'],'authority_key'=>$a['authority_key']];
        }
        return $out;
    }
    public static function validate(array $s,callable $load):void
    {
        R::require(count($s['applications'])<=256 && count($s['assessment_views'])<=256,'APPLICATION_HISTORY_LIMIT');
        $previous=null;$generation=-1;$views=[];$commands=[];$used=[];
        foreach(self::ordered($s) as $a){
            R::object($a,self::FIELDS);$h=R::record($a['receipt']);$b=R::object($h['body'],AssignmentRule::RECEIPT_FIELDS);
            R::require($h['schema']==='imperium.bootstrap-assignment-application/v1' && $h['producer']['service']==='onboarding.authority-admission'
                && $a['key']===R::key(R::reference($h)) && isset($s['applications'][$a['key']]) && R::same($s['applications'][$a['key']],$a),'APPLICATION_KEY');
            $policy=$load($b['policy_ref']);$sets=AssignmentRule::sets($policy,$load);$set=$sets[R::key($a['selected_set_ref'])]??null;R::require($set!==null,'APPLICATION_SET');
            $command=LedgerState::command($s,$a['command_ref']);$view=$load($b['result_ref']);$terms=$load($a['terms_ref']);
            R::require(isset($s['assessment_views'][R::key($b['result_ref'])]) && $view['schema']==='imperium.bootstrap-assessment-view/v1','APPLICATION_VIEW');
            R::head($b['commit_head']);R::require($b['commit_head']['generation']>$generation && R::same($b['commit_head'],$command['result']['observed_head']),'APPLICATION_ORDER');$generation=$b['commit_head']['generation'];
            R::require(R::same($command['result']['policy_ref'],$b['policy_ref']) && $h['created_at']>=$view['created_at'],'APPLICATION_COMMAND');
            $ck=R::hash($a['command_ref']);R::require(!isset($commands[$ck]),'APPLICATION_COMMAND_DUPLICATE');$commands[$ck]=true;
            $derived=AssignmentRule::derive($s,$policy,AssignmentRule::slot($policy),$load);
            R::require(R::same($view['body'],$derived['view_body']),'APPLICATION_VIEW_DERIVATION');
            $expectedViewSources=R::refs([$b['policy_ref'],...$view['body']['group_outcome_refs'],$view['body']['selection_rule_ref'],$view['body']['permitted_assignment_set_ref'],$derived['selected']['profile_evidence_ref']]);
            R::require(R::same($view['sources'],$expectedViewSources) && R::same($h['sources'],R::refs([$b['policy_ref'],$b['result_ref'],$a['terms_ref']])),'APPLICATION_SOURCES');
            $authority=$b['authority'];$admission=null;
            if($authority['kind']==='policy_effect'){
                R::object($authority,['kind','policy_ref','policy_admission_ref','slot_id','slot_digest','terms_ref','derivation_input_refs']);$slot=AssignmentRule::slot($policy);
                R::require($previous===null && $slot['authority_mode']==='policy_effect' && R::same($authority['policy_ref'],$b['policy_ref']) && $authority['slot_id']===$slot['slot_id']
                    && $authority['slot_digest']===R::hash($slot) && R::same($authority['terms_ref'],$derived['terms_ref']) && R::same($authority['derivation_input_refs'],$derived['derivation_input_refs']),'APPLICATION_POLICY_AUTHORITY');
                foreach($s['admissions'] as $k=>$r){if(R::same(R::reference($r),$authority['policy_admission_ref'])){$admission=Admission::retained($s,$k);}}
                R::require($admission!==null && $admission['envelope']['payload']['effect']==='AUTHORIZE_BOOTSTRAP_POLICY' && R::same($admission['object'],$policy),'APPLICATION_POLICY_ORIGINAL');
                $ak=[$policy['instance_id'],$policy['record_digest'],$slot['slot_id']];
            }else{
                R::object($authority,['kind','act_ref','admission_ref']);R::require($authority['kind']==='signed_act','APPLICATION_AUTHORITY');
                foreach($s['admissions'] as $k=>$r){if(R::same(R::reference($r),$authority['admission_ref'])){$admission=Admission::retained($s,$k);}}
                R::require($admission!==null && R::same(R::reference($admission['record']),$authority['act_ref']) && R::same($admission['object'],$terms),'APPLICATION_ACT_ORIGINAL');
                $p=Act::shape($admission['envelope'])['payload'];R::require($p['effect']==='APPLY_BOOTSTRAP_ASSIGNMENTS' && R::same($p['policy_ref'],$b['policy_ref']),'APPLICATION_ACT_SCOPE');
                R::require(sodium_crypto_sign_verify_detached(R::bytes($admission['envelope']['signature'],64),\App\Bootstrap\CanonicalJson::encode($p),R::bytes($s['trust']['body']['public_key'],32)),'APPLICATION_SIGNATURE');
                $ak=[$policy['instance_id'],$p['trust_fingerprint'],$p['nonce']];
            }
            $p=$admission['envelope']['payload'];R::require($p['issued_at']<=$h['created_at'] && $h['created_at']<min($p['expires_at'],$policy['body']['expires_at'],$s['trust']['body']['expires_at']),'APPLICATION_ADMISSION_TIME');
            R::require(sodium_crypto_sign_verify_detached(R::bytes($admission['envelope']['signature'],64),\App\Bootstrap\CanonicalJson::encode($p),R::bytes($s['trust']['body']['public_key'],32)),'APPLICATION_SIGNATURE');
            if($previous===null){R::require($h['created_at']<AssignmentRule::slot($policy)['expires_at'],'APPLICATION_SLOT_TIME');}
            R::require(R::same($a['authority_key'],$ak) && R::same($b['consumed_effect_ids'],[R::hash($ak)]) && !isset($used[R::hash($ak)]),'APPLICATION_CONSUMPTION');$used[R::hash($ak)]=true;
            if($previous===null){
                R::require($a['previous_application_ref']===null && $command['request']['schema']==='imperium.provider-onboarding-request/v2' && $command['request']['step_id']==='apply-assignments','APPLICATION_INITIAL');
                R::require(R::same($a['terms_ref'],$derived['terms_ref']) && R::same($a['selected_set_ref'],R::reference($derived['selected']['set'])),'APPLICATION_SELECTION');
                $prior=Policy::content($load($policy['body']['expected_assignments']),'expected-assignments');
                foreach($prior as $i=>$row){R::require($row===['role'=>AssignmentRule::ROLES[$i],'generation'=>0,'binding_ref'=>null] || R::same($row,['role'=>AssignmentRule::ROLES[$i],'generation'=>0,'binding_ref'=>null]),'APPLICATION_INITIAL_PREDECESSOR');}
                R::require(($authority['kind']==='policy_effect')===($policy['body']['application_mode']==='A'),'APPLICATION_MODE');
            }else{
                $q=$command['request'];CommandLedger::changeRequest(json_encode($q,JSON_THROW_ON_ERROR));$scope=ChangeAuthority::scope($policy,$terms,$load);$prior=$previous['receipt']['body']['next_assignments'];
                R::require($authority['kind']==='signed_act' && R::same($a['previous_application_ref'],R::reference($previous['receipt'])) && R::same($scope['expected_application_ref'],$a['previous_application_ref'])
                    && R::same($scope['prior_assignments'],$prior) && R::same($scope['next_set_ref'],$a['selected_set_ref']) && R::same($scope['assessment_view_ref'],$b['result_ref'])
                    && R::same($q['authority'],$authority) && R::same($q['terms_ref'],$a['terms_ref']) && R::same($q['predecessor_ref'],$previous['command_ref']),'CHANGE_HISTORY');
                R::require(R::same($b['policy_ref'],$previous['receipt']['body']['policy_ref']) && R::same($b['result_ref'],$previous['receipt']['body']['result_ref']),'CHANGE_EVIDENCE_SCOPE');
                self::eligible($set,AssignmentRule::parsed($s,$policy));AssignmentRule::profileEvidence($policy,$set,$load);
            }
            R::require(R::same($b['prior_assignments'],$prior) && R::same($b['next_assignments'],self::next($set,$prior)),'APPLICATION_PAIR');
            $views[R::key($b['result_ref'])]=true;$previous=$a;
        }
        R::require(count($views)===count($s['assessment_views']),'ORPHAN_ASSESSMENT_VIEW');
        foreach($s['assessment_views'] as $key=>$view){R::require($key===R::key(R::reference($view)) && isset($views[$key]),'ASSESSMENT_VIEW_KEY');}
        foreach($s['commands'] as $c){if(($c['request']['step_id']??null)==='apply-assignments' || ($c['request']['schema']??null)==='imperium.assignment-change/v1'){R::require(isset($commands[R::hash($c['ref'])]),'APPLICATION_COMMAND_MISSING');}}
    }
}
