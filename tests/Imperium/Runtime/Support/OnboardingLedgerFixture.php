<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore,Admission,Rules as R,Policy};
use App\Imperium\Runtime\Onboarding\Ledger\{CommandLedger,StateMigration,CustodyCoordinator,Recovery,PreparedOperation,SourceAuthority,CredentialCustody,ResponseCustody};
use App\Imperium\Runtime\Citadel\Formation\FormationJournal as J;
final class OnboardingLedgerFixture
{
    public OnboardingAuthorityFixture $f;public CommandLedger $ledger;public CustodyCoordinator $custody;public Recovery $recovery;public CountingOnboardingPorts $ports;public array $policy;public array $admission;public array $envelope;public ?array $last=null;private int $serial=0;private bool $owns=true;
    public function __construct(?CitadelFormationFixture $fc=null,array $sessions=[],bool $signed=false,array $budgetLimits=[]){
        $this->f=$f=new OnboardingAuthorityFixture();
        if($fc!==null){$f->close();$this->owns=false;$f->root=$fc->root;$f->now=$fc->clock->at;$f->store=new AuthorityStore($fc->root,$fc->clock,'instance-test',$fc->journal->read()['state']['citadel_id'],'operator-test',str_repeat('a',40));}
        $f->enroll();$p=$f->policy();$generic=$p['body']['budget_ref'];
        $limits=['calls'=>13,'input_tokens'=>196608,'output_tokens'=>49152,'cost_microusd'=>1200000,'milliseconds'=>730000];
        $limits=array_replace($limits,$budgetLimits);
        $root=$f->source('budget-root',['schema'=>'imperium.bootstrap-budget-root/v1','limits'=>$limits]);
        $bindings=[];foreach($sessions as $sid){$session=$fc->journal->read()['state']['sessions'][$sid];$bindings[]=['session_id'=>$sid,'resource_decision_digest'=>J::digest($session['provider_resource_decision'])];}
        $budget=$f->source('shared-budget',['schema'=>'imperium.bootstrap-budget-association/v1','lineage_ref'=>$root,'limits'=>$limits,'formation_sources'=>$bindings]);
        $p['body']['budget_ref']=$budget;
        $op=['schema'=>'imperium.bootstrap-prepared-operation/v1','wire'=>'','destination'=>'https://api.deepseek.com:443/models','method'=>'GET','provider'=>'deepseek','model'=>'access-listing-only','configuration_ref'=>$p['body']['candidate_bindings'][0]['configuration_ref'],'credential_operation'=>'synthetic-operation','adapter'=>'synthetic-fixed-onboarding','maximum'=>['calls'=>1,'input_tokens'=>0,'output_tokens'=>0,'cost_microusd'=>0,'milliseconds'=>10000],'expires_at'=>$f->now+300,'authority_source'=>['kind'=>'access','grant_ref'=>$generic,'executor'=>['kind'=>'infrastructure','adapter_ref'=>$p['body']['adapter_ref'],'deployment_custody_ref'=>$generic,'credential_binding_ref'=>$p['body']['credential_ref']]]];
        $operation=$f->source('access-operation',$op);
        foreach($p['body']['effect_slots'] as &$slot){
            if($signed && $slot['slot_id']==='slot.admit-evidence'){$slot['authority_mode']='signed_act';}
            if($slot['slot_id']==='slot.access'){
                $old=$slot['terms_rule']['object_ref'];$term=$f->sources[R::key($old)];unset($f->sources[R::key($old)]);
                unset($term['record_digest']);$term['body']['terms']=$operation;$term['sources']=[$operation];$term=R::seal($term);$f->sources[R::key(R::reference($term))]=$term;$slot['terms_rule']['object_ref']=R::reference($term);
            }
        }unset($slot);
        unset($p['record_digest']);$p['sources']=R::refs(array_map(R::reference(...),array_values($f->sources)));$this->policy=$p=R::seal($p);
        $this->envelope=$env=$this->sign($p,'AUTHORIZE_BOOTSTRAP_POLICY');
        $this->admission=(new Admission($f->store))->retain($f::json($env),$f::json($p),array_map($f::json(...),array_values($f->sources)));
        if($signed){$terms=$f->sources[R::key($p['body']['effect_slots'][0]['terms_rule']['object_ref'])];(new Admission($f->store))->retain($f::json($this->sign($terms,'ADMIT_BOOTSTRAP_EVIDENCE',R::reference($p))),$f::json($terms));}
        (new StateMigration($f->store))->migrate($f->head());
        $this->ports=new CountingOnboardingPorts($f->root,$op);$this->ledger=new CommandLedger($f->store,$this->ports,$this->ports);$this->custody=new CustodyCoordinator($this->ledger,$this->ports,$this->ports,$this->ports);$this->recovery=new Recovery($this->ledger,$this->custody);
    }
    public function sign(array $h,string $effect,?array $policy=null):array{$e=$this->f->sign($h,$effect,$policy);$e['payload']['citadel_id']=$this->f->store->citadel;return $this->f->resign($e);}
    public function request(?string $step=null,?string $id=null):array {
        $h=$this->f->head();if($h['digest']!==null){$h['digest']='sha256:'.$h['digest'];}
        return ['schema'=>'imperium.provider-onboarding-request/v2','sequence_id'=>'sequence-test','command_id'=>$id??'command-'.str_pad((string)++$this->serial,8,'0',STR_PAD_LEFT),'mode'=>'advance','instance_id'=>'instance-test','policy_ref'=>['id'=>$this->policy['id'],'version'=>$this->policy['body']['policy_version'],'digest'=>$this->policy['record_digest']],'expected_head'=>$h,'predecessor_ref'=>$this->last,'step_id'=>$step,'evidence_refs'=>[]];
    }
    public function advance(?string $step=null,bool $dispatch=false):array{$q=$this->request($step);$r=($dispatch?$this->custody:$this->ledger)->advance($this->f::json($q));$this->last=$r['result_ref'];return $r;}
    public function ready():void{$this->advance();$this->advance('configure');$this->advance('admit-evidence');}
    public function close():void{if($this->owns){$this->f->close();}}
}
final class CountingOnboardingPorts implements PreparedOperation,SourceAuthority,CredentialCustody,ResponseCustody
{
    public array $counts=['issue'=>0,'consume'=>0,'dispatch'=>0];public ?\Closure $hook=null;public string $fault='';public bool $repeat=false;public bool $fakeConsume=false;
    public function __construct(private string $root,public array $operation){}
    private function mark(string $stage):void{if(isset($this->counts[$stage])){++$this->counts[$stage];file_put_contents($this->root.'/b1-counts.json',json_encode($this->counts));}if($this->hook!==null){($this->hook)($stage);}if($this->fault===$stage){throw new \RuntimeException('synthetic fault');}}
    public function prepare(array $terms):array{
        return $this->operation;
    }
    public function verify(AuthorityStore $store,array $state,array $policy,string $effect,array $terms,array $operation):void{R::require($effect==='AUTHORIZE_BOOTSTRAP_ACCESS' && R::same(Policy::content($store->checkSource($state['onboarding'],$terms['body']['terms']),'access-operation'),$operation),'SYNTHETIC_SOURCE_MISMATCH');}
    public function validateResponse(AuthorityStore $store,array $state,array $policy,string $effect,array $operation,array $envelope):string{R::require($effect==='AUTHORIZE_BOOTSTRAP_ACCESS' && R::same(json_decode($envelope['response'],true),['listed'=>true]),'SYNTHETIC_RESPONSE_INVALID');return 'SUCCEEDED';}
    public function select(AuthorityStore $store,array $state,array $policy,array $step):array{throw new \RuntimeException('O2_COMPATIBLE_PRODUCER_MISSING');}
    public function issue(array $claim,array $operation):object{$this->mark('before-issue');$this->mark('issue');return (object)['claim'=>R::reference($claim),'operation'=>R::hash($operation),'used'=>false];}
    public function consume(object $capability,array $claim,array $operation,callable $delivery):void{
        R::require(!$capability->used && R::same($capability->claim,R::reference($claim)) && $capability->operation===R::hash($operation),'SYNTHETIC_CAPABILITY');$capability->used=true;$this->mark('before-consume');$this->mark('consume');if($this->fakeConsume){return;}$delivery('synthetic-secret-not-real');if($this->repeat){$delivery('synthetic-secret-not-real');}
    }
    public function dispatch(array $operation,#[\SensitiveParameter] mixed $authentication):array{$this->mark('before-dispatch');$this->mark('dispatch');return ['response'=>'{"listed":true}','provider_response_id'=>'synthetic-response','operation_digest'=>R::hash($operation),'usage'=>['calls'=>1,'input_tokens'=>0,'output_tokens'=>0,'cost_microusd'=>0,'milliseconds'=>5],'provenance'=>$operation['adapter']];}
    public function retain(array $envelope):void{$this->mark('before-envelope');$path=$this->root.'/b1-envelope-'.substr($envelope['claim_ref']['digest'],7).'.json';R::require(!is_file($path) || R::same(json_decode(file_get_contents($path),true),$envelope),'ENVELOPE_CONFLICT');file_put_contents($path,json_encode($envelope));$this->mark('after-envelope');}
    public function read(array $ref):array{$path=$this->root.'/b1-envelope-'.substr($ref['digest'],7).'.json';R::require(is_file($path),'RESPONSE_ORIGINAL_MISSING');return json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);}
}
