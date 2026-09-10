<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Ledger;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules as R,Policy};
use App\Imperium\Runtime\Citadel\Formation\SharedExposure;
/** The only delivery entry admits a new command in this process. History never dispatches. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class CustodyCoordinator
{
    public const STAGES=['DELIVERY_COMMITTED_OUTCOME_UNCERTAIN','CONSUMPTION_COMMITTED_OUTCOME_UNCERTAIN','DISPATCH_COMMITTED_OUTCOME_UNCERTAIN','RESPONSE_VALIDATED_PENDING_ENVELOPE','RESPONSE_RETAINED'];
    public function __construct(private CommandLedger $ledger,private PreparedOperation $preparer=new RefusingPorts(),private CredentialCustody $credentials=new RefusingPorts(),private ResponseCustody $responses=new RefusingPorts()){}
    public function advance(string $raw):array {
        $result=$this->ledger->advance($raw);if($result['status']!=='STEP_ADMITTED'){return $result;}
        $store=$this->ledger->store;
        $id=$store->journal->inspect(function(array $frame)use($result):?string {
            foreach($this->ledger->store->state($frame['state'])['claims'] as $id=>$c){if(R::same($c['record']['body']['command_ref'],$result['result_ref'])){return $id;}}return null;
        });
        if($id===null){return $result;}
        try{
            $claim=$this->checkpoint($id,0);$op=$claim['operation']['prepared'];
            $capability=$this->credentials->issue($claim['record'],$op);
            $this->checkpoint($id,1);
            $this->credentials->consume($capability,$claim['record'],$op,function(#[\SensitiveParameter] mixed $authentication)use($id,$op):void{
                $c=$this->checkpoint($id,2);
                R::require(is_string($authentication) && $authentication!=='','CREDENTIAL_CONTEXT');
                $response=$this->responses->dispatch($op,$authentication);
                R::object($response,['response','provider_response_id','operation_digest','usage','provenance']);
                R::require(is_string($response['response']) && strlen($response['response'])<=1048576 && !str_contains($response['response'],$authentication),'RESPONSE_SCOPE');
                foreach(['provider_response_id','provenance','operation_digest'] as $f){R::require(is_string($response[$f]) && $response[$f]!=='' && !str_contains($response[$f],$authentication),'RESPONSE_SCOPE');}
                R::require($response['operation_digest']===R::hash($op) && $response['provenance']===$op['adapter'],'RESPONSE_ATTRIBUTION');
                SharedExposure::meters($response['usage']);foreach($response['usage'] as $f=>$n){R::require($n<=$c['maximum'][$f],'USAGE_UNTRUSTWORTHY');}
                $metadata=['provider_response_id'=>$response['provider_response_id'],'operation_digest'=>$response['operation_digest'],'response_digest'=>R::hash($response['response']),'usage'=>$response['usage'],'provenance'=>$response['provenance']];
                $this->checkpoint($id,3,$metadata);
                $envelope=['claim_ref'=>R::reference($c['record']),'operation_digest'=>R::hash($op),'response'=>$response['response'],'metadata'=>$metadata];
                $this->responses->retain($envelope);
                $retained=$this->responses->read(R::reference($c['record']));R::require(R::same($retained,$envelope),'RESPONSE_ORIGINAL_MISSING');
                $this->checkpoint($id,4,$metadata,$retained);
            });
            // A broker return without the actual callback has no evidentiary value.
            $this->finish($id);
        }catch(\Throwable){throw new \RuntimeException('O2_CUSTODY_REFUSED_OR_OUTCOME_UNKNOWN');}
        return $result;
    }
    public function check(array $state,string $id):array {
        $store=$this->ledger->store;$s=$store->state($state);$claim=$s['claims'][$id]??null;R::require(is_array($claim),'CLAIM_ORIGINAL_MISSING');
        $command=LedgerState::command($s,$claim['record']['body']['command_ref']);$policy=$this->ledger->policy($s,$command['request']['policy_ref']);
        $step=StepReadiness::ready($store,$s,$policy,$command['result']['step_id']);[$a,$slot,$facts]=$this->ledger->authority($s,$policy,$step);
        CompletionResolver::resolve($this->ledger->store,$s,$policy,$facts['obligations']);
        R::require(R::same($a,$claim['record']['body']['authority_source']['authority']),'CLAIM_AUTHORITY');
        $cons=$s['steps'][LedgerState::key('step',[$store->instance,$policy['record_digest'],$step['step_id']])]['consumption'];
        R::require(R::same($cons['body']['command_ref'],$command['ref']) && R::same($cons['body']['authority_consumption'],$claim['record']['body']['authority_consumption']),'CLAIM_CONSUMPTION');
        $slotRecord=$s['slots'][LedgerState::key('slot',[$store->instance,$policy['record_digest'],$slot['slot_id']])]??null;
        R::require(is_array($slotRecord) && R::same($slotRecord['command_ref'],$command['ref']),'CLAIM_CONSUMPTION');
        $op=$this->preparer->prepare($facts['terms']);R::require(R::same($op,$claim['operation']['prepared']),'PREPARED_OPERATION_CHANGED');
        $this->ledger->operation($state,$policy,$slot['effect'],$facts['terms'],$op);
        R::require($claim['settled']===null && $store->now()<$slot['expires_at'],'CLAIM_CURRENT');
        $budget=BudgetAssociation::resolve($store,$state,$policy);SharedExposure::check($state,$budget['identity'],$budget['limits'],$claim['maximum'],['onboarding',$id]);return $claim;
    }
    private function checkpoint(string $id,int $index,?array $metadata=null,?array $envelope=null):array{
        $store=$this->ledger->store;
        return $store->journal->changeAtHead(function(array &$state,array $head)use($id,$index,$metadata,$envelope,$store):array {
            $c=$this->check($state,$id);R::require(count($c['custody'])===$index,'CUSTODY_ALREADY_CONSUMED');
            $prior=$index===0?null:R::reference($c['custody'][$index-1]);
            if($index===4){$old=$c['custody'][3]['body']['response_metadata'];R::require(R::same($metadata,$old) && R::same($envelope['metadata'],$old) && R::same($envelope['claim_ref'],R::reference($c['record'])) && R::hash($envelope['response'])===$old['response_digest'],'RESPONSE_ORIGINAL_MISSING');}
            $record=$store->make('imperium.bootstrap-custody-checkpoint/v1','custody-'.substr(R::hash([$id,$index]),7,24),['claim_ref'=>R::reference($c['record']),'operation_digest'=>R::hash($c['operation']),'stage'=>self::STAGES[$index],'previous_ref'=>$prior,'response_metadata'=>$metadata,'response_envelope'=>$envelope],$prior===null?[R::reference($c['record'])]:[R::reference($c['record']),$prior]);
            $state['onboarding']['claims'][$id]['custody'][]=$record;return $state['onboarding']['claims'][$id];
        });
    }
    public function finish(string $id):array {
        $store=$this->ledger->store;
        return $store->journal->changeAtHead(function(array &$state)use($id,$store):array {
            $s=$store->state($state);$c=$s['claims'][$id]??null;R::require(is_array($c),'CLAIM_ORIGINAL_MISSING');
            $cmd=LedgerState::command($s,$c['record']['body']['command_ref']);$stepKey=LedgerState::key('step',[$store->instance,$c['record']['body']['policy_ref']['digest'],$cmd['result']['step_id']]);
            if(isset($s['attempt_outcomes'][$id]) && $c['settled']!==null){return $s['attempt_outcomes'][$id];}
            if($s['steps'][$stepKey]['completion']!==null){return $s['steps'][$stepKey]['completion'];}
            $this->check($state,$id);R::require(count($c['custody'])===5,'OUTCOME_UNKNOWN');$last=$c['custody'][4];$envelope=$last['body']['response_envelope'];
            R::require(is_array($envelope) && R::hash($envelope['response'])===$last['body']['response_metadata']['response_digest'],'RESPONSE_ORIGINAL_MISSING');
            $classification=$this->ledger->validateResponse($state,$c,$envelope);
            if($c['record']['body']['authority_source']['kind']==='assessment'){$outcome=AssessmentGroups::recordOutcome($store,$s,$c,$classification);$state['onboarding']['attempt_outcomes'][$id]=$outcome;}else{R::require($classification==='SUCCEEDED','ACCESS_RESPONSE_INVALID');}
            $completion=$this->ledger->complete($cmd['ref'],$s['steps'][$stepKey]['consumption'],$c['record']['body']['authority_source']['kind']==='access'?'AUTHORIZE_BOOTSTRAP_ACCESS':'AUTHORIZE_BOOTSTRAP_ASSESSMENT',[R::reference($last)]);
            $state['onboarding']['claims'][$id]['settled']=$envelope['metadata']['usage'];$state['onboarding']['steps'][$stepKey]['completion']=$classification==='SUCCEEDED'?$completion:null;
            unset($state['onboarding']['source_fences'][$id]);return $completion;
        });
    }
    /** Metadata proves attribution; an external caller body can never recover a result. */
    public function reconcile(string $id):array {
        $c=$this->ledger->store->journal->inspect(fn(array $f):array=>$this->ledger->store->state($f['state'])['claims'][$id]??throw new \RuntimeException('O2_CLAIM_ORIGINAL_MISSING'));
        if(count($c['custody'])===4){$envelope=$this->responses->read(R::reference($c['record']));$this->checkpoint($id,4,$c['custody'][3]['body']['response_metadata'],$envelope);}
        return $this->finish($id);
    }
}
