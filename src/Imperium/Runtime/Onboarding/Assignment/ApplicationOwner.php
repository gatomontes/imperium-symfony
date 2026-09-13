<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Assignment;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules as R,Policy,StrictJson,CurrentAuthority};
use App\Imperium\Runtime\Onboarding\Ledger\LedgerState;
use App\Imperium\Runtime\Onboarding\Augur\AugurAdapter;

/** Private implementation of the CommandLedger owning boundary. */
trait ApplicationOwner
{
    use OriginalAssessment;
    private function assessmentAdapter():AugurAdapter
    {
        R::require($this->sources instanceof AugurAdapter,'ASSIGNMENT_ADAPTER_REQUIRED');return $this->sources;
    }
    private function prepareApplication(array $state,array $s,array $policy,?array $setRef=null):array
    {
        R::require($s['schema']===AssignmentMigration::SCHEMA && count($s['applications'])<256,'ASSIGNMENT_STATE');
        $originals=$this->originals($state,R::reference($policy));$retained=[];
        $load=function(array $ref)use($s,&$retained):array{$h=$this->store->checkSource($s,$ref);$retained[R::key($ref)]=$h;return $h;};
        $projection=AssignmentRule::select($originals,$load);
        if($setRef!==null){$sets=AssignmentRule::sets($policy,$load);R::require(isset($sets[R::key($setRef)]),'CHANGE_SET_SCOPE');$projection['selected']=$sets[R::key($setRef)];}
        $set=$projection['selected'];ApplicationHistory::eligible($set,$originals);AssignmentRule::profileEvidence($policy,$set,$load);
        foreach($policy['body']['candidate_bindings'] as $binding){foreach(['binding_ref','configuration_ref','adapter_ref','mapping_ref'] as $field){$load($binding[$field]);}}
        $load($policy['body']['credential_ref']);
        $this->assignmentEvidence->verify($policy,$set['assignments'],$retained,$originals['groups']);
        return $projection;
    }
    private function publishApplication(array &$s,array $policy,array $projection,array $authority,array $ak,array $commandRef,array $head,?array $previous=null,?array $changeTerms=null):array
    {
        if($previous===null){
            R::require($s['applications']===[] && $s['assessment_views']===[],'INITIAL_ASSIGNMENT_ALREADY_CONSUMED');
            $prior=Policy::content($this->store->checkSource($s,$policy['body']['expected_assignments']),'expected-assignments');
            R::require(R::same($prior,array_map(static fn(string $role):array=>['role'=>$role,'generation'=>0,'binding_ref'=>null],AssignmentRule::ROLES)),'STALE_ASSIGNMENT_PREDECESSOR');
            $body=$projection['view_body'];
            $view=$this->store->make('imperium.bootstrap-assessment-view/v1','view-'.substr(R::hash($body),7,24),$body,
                [R::reference($policy),...$body['group_outcome_refs'],$body['selection_rule_ref'],$body['permitted_assignment_set_ref'],$projection['selected']['profile_evidence_ref']]);
            $s['assessment_views'][R::key(R::reference($view))]=$view;$terms=$projection['terms_ref'];
        }else{
            $prior=$previous['receipt']['body']['next_assignments'];$view=$s['assessment_views'][R::key($previous['receipt']['body']['result_ref'])];$terms=$changeTerms;
        }
        $set=$projection['selected'];
        $receipt=$this->store->make('imperium.bootstrap-assignment-application/v1','application-'.substr(R::hash($commandRef),7,24),[
            'policy_ref'=>R::reference($policy),'result_ref'=>R::reference($view),'authority'=>$authority,'prior_assignments'=>$prior,
            'next_assignments'=>ApplicationHistory::next($set,$prior),'consumed_effect_ids'=>[R::hash($ak)],'commit_head'=>$head],
            [R::reference($policy),R::reference($view),$terms]);
        $key=R::key(R::reference($receipt));$s['applications'][$key]=['key'=>$key,'receipt'=>$receipt,'command_ref'=>$commandRef,
            'previous_application_ref'=>$previous===null?null:R::reference($previous['receipt']),'terms_ref'=>$terms,'selected_set_ref'=>R::reference($set['set']),'authority_key'=>$ak];
        return [R::reference($receipt)];
    }
    public static function changeRequest(string $raw):array
    {
        R::require(strlen($raw)<=1048576,'REQUEST_LIMIT');$q=StrictJson::decode($raw);
        R::object($q,['schema','sequence_id','command_id','mode','instance_id','policy_ref','expected_head','predecessor_ref','step_id','evidence_refs','authority','terms_ref']);
        R::require($q['schema']==='imperium.assignment-change/v1' && $q['mode']==='advance' && $q['step_id']===null && $q['evidence_refs']===[],'CHANGE_REQUEST');
        $base=$q;unset($base['authority'],$base['terms_ref']);$base['schema']='imperium.provider-onboarding-request/v2';self::request(json_encode($base,JSON_THROW_ON_ERROR));
        LedgerState::commandRef($q['predecessor_ref']);R::ref($q['terms_ref']);R::object($q['authority'],['kind','act_ref','admission_ref']);R::require($q['authority']['kind']==='signed_act','CHANGE_AUTHORITY');
        R::ref($q['authority']['act_ref']);R::ref($q['authority']['admission_ref']);return $q;
    }
    public function replace(string $raw):array
    {
        $q=self::changeRequest($raw);R::require($q['instance_id']===$this->store->instance,'FOREIGN_RECORD');
        return $this->store->journal->changeAtHead(function(array &$state,array $head)use($q,$raw):array{return StrictJson::within(function()use(&$state,$head,$q,$raw):array{
            $s=$this->store->state($state);$key=LedgerState::key('command',[$this->store->instance,$q['sequence_id'],$q['command_id']]);
            if(isset($s['commands'][$key])){R::require(R::same($s['commands'][$key]['request'],$q),'COMMAND_CONFLICT');return self::presentation($s['commands'][$key],$head,true);}
            R::require($s['schema']===AssignmentMigration::SCHEMA && count($s['commands'])<4096 && $s['source_fences']===[],'CHANGE_STATE');
            R::require(R::same(self::head($q['expected_head']),$head),'STALE_HEAD');$policy=$this->policy($s,$q['policy_ref']);$previous=ApplicationHistory::latest($s);
            R::require($previous!==null && R::same($previous['command_ref'],$q['predecessor_ref']) && $previous['command_ref']['sequence_id']===$q['sequence_id']
                && R::same($previous['receipt']['body']['policy_ref'],R::reference($policy)),'STALE_ASSIGNMENT_PREDECESSOR');
            $facts=CurrentAuthority::verify($this->store,$s,$q['authority'],'APPLY_BOOTSTRAP_ASSIGNMENTS',$q['terms_ref']);
            $scope=ChangeAuthority::scope($policy,$facts['terms'],fn(array $ref):array=>$this->store->checkSource($s,$ref));
            R::require(R::same($scope['expected_application_ref'],R::reference($previous['receipt'])) && R::same($scope['prior_assignments'],$previous['receipt']['body']['next_assignments'])
                && R::same($scope['assessment_view_ref'],$previous['receipt']['body']['result_ref']),'STALE_ASSIGNMENT_PREDECESSOR');
            $projection=$this->prepareApplication($state,$s,$policy,$scope['next_set_ref']);$payload=$facts['admission']['envelope']['payload'];
            $ak=[$this->store->instance,$payload['trust_fingerprint'],$payload['nonce']];foreach($s['slots'] as $slot){R::require(!R::same($slot['authority_key'],$ak),'AUTHORITY_ALREADY_CONSUMED');}
            $command=$this->command($q,$raw,$head,$policy,'ASSIGNMENTS_CHANGED');
            $this->publishApplication($s,$policy,$projection,$q['authority'],$ak,$command['ref'],$head,$previous,$q['terms_ref']);
            $tuple=[$this->store->instance,$policy['record_digest'],'slot.change-'.$payload['nonce']];
            $s['slots'][LedgerState::key('slot',$tuple)]=['key'=>$tuple,'command_ref'=>$command['ref'],'authority_key'=>$ak];$s['commands'][$key]=$command;
            LedgerState::validate($s);$state['onboarding']=$s;return self::presentation($command,$head,false);
        }); });
    }
}
