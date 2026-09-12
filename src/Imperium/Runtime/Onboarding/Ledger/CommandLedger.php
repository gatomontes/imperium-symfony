<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Ledger;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore,Rules as R,StrictJson,CurrentAuthority,Admission};
use App\Imperium\Runtime\Citadel\Formation\SharedExposure;
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class CommandLedger
{
    public function __construct(public AuthorityStore $store,private PreparedOperation $preparer=new RefusingPorts(),private SourceAuthority $sources=new RefusingPorts(),private ?\App\Imperium\Runtime\Onboarding\Augur\FreshProducer $founding=null) {}
    public static function request(string $raw): array {
        R::require(strlen($raw)<=1048576,'REQUEST_LIMIT');$q=StrictJson::decode($raw);
        R::object($q,['schema','sequence_id','command_id','mode','instance_id','policy_ref','expected_head','predecessor_ref','step_id','evidence_refs']);
        R::require($q['schema']==='imperium.provider-onboarding-request/v2' && in_array($q['mode'],['preview','advance'],true),'REQUEST_SCHEMA');
        foreach(['sequence_id','command_id','instance_id'] as $k){R::id($q[$k]);}
        R::object($q['policy_ref'],['id','version','digest']);R::id($q['policy_ref']['id']);R::id($q['policy_ref']['version']);R::digest($q['policy_ref']['digest']);
        self::head($q['expected_head']);if($q['predecessor_ref']!==null){R::object($q['predecessor_ref'],['sequence_id','command_id','request_digest','result_digest']);}
        R::require($q['step_id']===null || is_string($q['step_id']) && preg_match('/\A[a-z0-9][a-z0-9._-]{0,79}\z/',$q['step_id'])===1,'STEP_ID');
        R::require(count($q['evidence_refs'])<=256 && R::same(R::refs($q['evidence_refs']),$q['evidence_refs']),'EVIDENCE_REFS');return $q;
    }
    public static function head(array $h): array {R::object($h,['generation','digest']);R::integer($h['generation']);if($h['generation']===0){R::require($h['digest']===null,'HEAD');}else{R::digest($h['digest']);$h['digest']=substr($h['digest'],7);}return R::head($h);}
    public function advance(string $raw): array {
        $q=self::request($raw);R::require($q['instance_id']===$this->store->instance,'FOREIGN_RECORD');
        return $this->store->journal->changeAtHead(function(array &$state,array $head)use($q,$raw):array {
            $s=$this->store->state($state);R::require(in_array($s['schema'],['imperium.onboarding-authority-state/v2','imperium.onboarding-authority-state/v3'],true),'STATE_MIGRATION_REQUIRED');
            $tuple=[$this->store->instance,$q['sequence_id'],$q['command_id']];$key=LedgerState::key('command',$tuple);
            if(isset($s['commands'][$key])){R::require(R::same($s['commands'][$key]['request'],$q),'COMMAND_CONFLICT');return self::presentation($s['commands'][$key],$head,true);}
            R::require(R::same(self::head($q['expected_head']),$head),'STALE_HEAD');$policy=$this->policy($s,$q['policy_ref']);
            $sk=LedgerState::key('sequence',[$this->store->instance,$q['sequence_id']]);$seq=$s['sequences'][$sk]??null;
            R::require(count($s['commands'])<4096,'LIMIT_EXCEEDED');
            if($seq===null){
                R::require($q['step_id']===null && $q['predecessor_ref']===null,'SEQUENCE_REGISTRATION_REQUIRED');
                foreach($s['sequences'] as $prior){R::require(!R::same($prior['registration']['body']['policy_ref'],R::reference($policy)),'SEQUENCE_ALREADY_REGISTERED');}
                R::require(count($s['sequences'])<256,'LIMIT_EXCEEDED');
                foreach($q['evidence_refs'] as $ref){$this->store->checkSource($s,$ref);}
                $budget=BudgetAssociation::resolve($this->store,$state,$policy);
                $sourceMap=$budget['source_bindings'];
                foreach($s['budget_bindings'] as $prior){$pb=$prior['record']['body'];R::require($pb['budget_identity']===$budget['identity'],'INDEPENDENT_BUDGET_PROOF_MISSING');foreach($pb['source_bindings'] as $a){foreach($sourceMap as $b){if(R::same($a,$b)){R::require($pb['budget_identity']===$budget['identity'],'BUDGET_REMAP');}}}}
                $command=$this->command($q,$raw,$head,$policy,'REGISTERED');
                $reg=$this->store->make('imperium.bootstrap-sequence-registration/v1','sequence-'.substr($sk,7,24),['sequence_ref'=>['instance_id'=>$this->store->instance,'sequence_id'=>$q['sequence_id']],'policy_ref'=>R::reference($policy),'budget_ref'=>$policy['body']['budget_ref'],'initial_evidence_refs'=>$q['evidence_refs'],'registration_command_ref'=>$command['ref']]);
                $s['sequences'][$sk]=['key'=>[$this->store->instance,$q['sequence_id']],'registration'=>$reg,'head'=>$command['ref']];
                $bt=[$budget['identity'],$policy['record_digest']];$bk=LedgerState::key('budget',$bt);
                $s['budget_bindings'][$bk]=['key'=>$bt,'record'=>$this->store->make('imperium.bootstrap-budget-binding/v1','budget-'.substr($bk,7,24),['budget_ref'=>$policy['body']['budget_ref'],'budget_identity'=>$budget['identity'],'limit_ref'=>$budget['root_ref'],'source_bindings'=>$sourceMap,'predecessor_head'=>$head],[$policy['body']['budget_ref'],$budget['root_ref']])];
            }else{
                R::require(R::same($seq['head'],$q['predecessor_ref']),'STALE_PREDECESSOR');R::require(R::same($seq['registration']['body']['policy_ref'],R::reference($policy)),'SEQUENCE_POLICY');
                R::require(R::same($seq['registration']['body']['initial_evidence_refs'],$q['evidence_refs']),'FROZEN_EVIDENCE');
                $previous=LedgerState::command($s,$seq['head']);if($previous['result']['step_id']!==null){LedgerState::step($s,$policy,$previous['result']['step_id']);}
                R::require($q['step_id']!==null,'STEP_REQUIRED');R::require($s['source_fences']===[],'OUTCOME_UNKNOWN');$step=StepReadiness::ready($this->store,$s,$policy,$q['step_id']);
                $st=[$this->store->instance,$policy['record_digest'],$step['step_id']];$stepKey=LedgerState::key('step',$st);R::require(!isset($s['steps'][$stepKey]),'STEP_ALREADY_CONSUMED');
                $authority=null;$ak=null;$op=null;$facts=null;$slot=null;$results=[];
                if($step['effect_slot_id']!==null){
                    [$authority,$slot,$facts]=$this->authority($s,$policy,$step);CompletionResolver::resolve($this->store,$s,$policy,$facts['obligations']);
                    R::require(($slot['effect']==='CONSTITUTE_FOUNDING_AUGUR' && $s['schema']==='imperium.onboarding-authority-state/v3' && $this->founding!==null) || in_array($slot['effect'],['ADMIT_BOOTSTRAP_EVIDENCE','APPROVE_RUNTIME_BINDING_MAP','AUTHORIZE_BOOTSTRAP_ACCESS','AUTHORIZE_BOOTSTRAP_ASSESSMENT'],true),'DYNAMIC_PREREQUISITES_MISSING');
                    $ak=$authority['kind']==='signed_act'?[$this->store->instance,$facts['admission']['envelope']['payload']['trust_fingerprint'],$facts['admission']['envelope']['payload']['nonce']]:[$this->store->instance,$policy['record_digest'],$slot['slot_id']];
                    $slotKey=LedgerState::key('slot',[$this->store->instance,$policy['record_digest'],$slot['slot_id']]);R::require(!isset($s['slots'][$slotKey]),'SLOT_ALREADY_CONSUMED');
                    foreach($s['slots'] as $v){R::require(!R::same($v['authority_key'],$ak),'AUTHORITY_ALREADY_CONSUMED');}
                    if(in_array($slot['effect'],['AUTHORIZE_BOOTSTRAP_ACCESS','AUTHORIZE_BOOTSTRAP_ASSESSMENT'],true)){
                        $op=$this->preparer instanceof ContextualPreparedOperation?$this->preparer->prepareCurrent($this->store,$state,$policy,$step,$facts['terms']):$this->preparer->prepare($facts['terms']);R::require($op['expires_at']<=min($slot['expires_at'],$facts['admission']['envelope']['payload']['expires_at']),'LEASE_SCOPE');$this->operation($state,$policy,$slot['effect'],$facts['terms'],$op);
                        if($slot['effect']==='AUTHORIZE_BOOTSTRAP_ASSESSMENT'){AssessmentGroups::freeze($this->store,$s,$policy,$step,$op);}
                        $budget=BudgetAssociation::resolve($this->store,$state,$policy);SharedExposure::check($state,$budget['identity'],$budget['limits'],$op['maximum']);
                    }elseif($slot['effect']!=='CONSTITUTE_FOUNDING_AUGUR'){
                        $source=$this->store->checkSource($s,$facts['terms']['body']['terms']);
                        R::require($source['schema']==='imperium.bootstrap-source/v1','EFFECT_SOURCE');
                        if($slot['effect']==='APPROVE_RUNTIME_BINDING_MAP'){
                            $mapping=\App\Imperium\Runtime\Onboarding\AuthorityAdmission\Policy::content($source,'runtime-binding-map');
                            R::object($mapping,['snapshot_ref','provider','mappings','adapter_ref','expires_at']);
                            R::require($mapping['provider']===$policy['body']['provider'] && $this->store->now()<R::time($mapping['expires_at']) && $mapping['expires_at']<=$policy['body']['expires_at'],'MAPPING_SCOPE');
                            foreach(['snapshot_ref','adapter_ref'] as $field){$this->store->checkSource($s,R::ref($mapping[$field]));}
                            R::require(is_array($mapping['mappings']) && array_is_list($mapping['mappings']) && count($mapping['mappings'])>0 && count($mapping['mappings'])<=256,'MAPPING_SCOPE');$seen=[];
                            foreach($mapping['mappings'] as $row){R::object($row,['model_ref','dispatch_id','configuration_schema_digest','supported_limits','revision_pin_limitation']);$this->store->checkSource($s,R::ref($row['model_ref']));R::text($row['dispatch_id']);R::digest($row['configuration_schema_digest']);R::text($row['revision_pin_limitation']);R::require(!isset($seen[R::key($row['model_ref'])]),'MAPPING_DUPLICATE');$seen[R::key($row['model_ref'])]=true;SharedExposure::meters($row['supported_limits'],false);}
                        }
                        $results=[R::reference($source)];
                    }
                }elseif($step['action']==='RECORD_CONFIGURATION'){$unique=[];foreach($policy['body']['candidate_bindings'] as $binding){$unique[R::key($binding['configuration_ref'])]=$binding['configuration_ref'];}$results=R::refs(array_values($unique));}
                elseif($step['action']==='SELECT_BASE'){$results=$this->sources->select($this->store,$state,$policy,$step);R::refs($results);R::require($results!==[],'BASE_RESULT_MISSING');foreach($results as $ref){$this->store->checkSource($s,$ref);}}
                else{throw new \RuntimeException('O2_UNSUPPORTED_ACTION');}
                $command=$this->command($q,$raw,$head,$policy,'STEP_ADMITTED');
                $cons=$ak===null?null:['key'=>$ak,'consumed' => true,'commit_head'=>$head];
                $operationRecord=$op===null?null:$this->store->make('imperium.bootstrap-prepared-operation-record/v1','operation-'.substr(R::hash($op),7,24),['operation'=>$op]);
                $consumption=$this->store->make('imperium.bootstrap-step-consumption/v1','step-'.substr($stepKey,7,24),['sequence_ref'=>['instance_id'=>$this->store->instance,'sequence_id'=>$q['sequence_id'],'policy_digest'=>$policy['record_digest']],'command_ref'=>$command['ref'],'policy_ref'=>R::reference($policy),'step_id'=>$step['step_id'],'slot_id'=>$step['effect_slot_id'],'authority_consumption'=>$cons,'prepared_operation_ref'=>$operationRecord===null?null:R::reference($operationRecord),'predecessor_head'=>$head]);
                if(($slot['effect']??null)==='CONSTITUTE_FOUNDING_AUGUR'){$results=$this->founding->publish($this->store,$s,$policy,$slot,$facts['terms'],$command['ref'],$head);}
                $completion=$op===null?$this->complete($command['ref'],$consumption,$slot['effect']??null,$results):null;
                $s['steps'][$stepKey]=['key'=>$st,'consumption'=>$consumption,'completion'=>$completion];
                if($slot!==null){$s['slots'][$slotKey]=['key'=>[$this->store->instance,$policy['record_digest'],$slot['slot_id']],'command_ref'=>$command['ref'],'authority_key'=>$ak];}
                if($op!==null){
                    $ct=[$command['ref'],$step['step_id'],R::hash($op)];$ck=LedgerState::key('claim',$ct);
                    $source=$op['authority_source'];$source['authority']=$authority;
                    $lease=$source['kind']==='access'?['kind'=>'access','grant_ref'=>$source['grant_ref'],'executor_digest'=>R::hash($source['executor'])]:['kind'=>'assessment','commission_ref'=>$source['commission_ref'],'holder_ref'=>$source['holder_ref']];
                    $claim=$this->store->make('imperium.bootstrap-cognition-claim/v1','claim-'.substr($ck,7,24),['sequence_ref'=>$consumption['body']['sequence_ref'],'command_ref'=>$command['ref'],'policy_ref'=>R::reference($policy),'authority_source'=>$source,'prepared_operation_ref'=>R::reference($operationRecord),'budget_ref'=>$policy['body']['budget_ref'],'authority_consumption'=>$cons,'lease_consumption'=>[...$lease,'expires_at'=>$op['expires_at'],'consumed' => true,'commit_head'=>$head],'custody_checkpoint'=>'RESERVED','response_ref'=>null]);
                    $s['claims'][$ck]=['key'=>$ct,'record'=>$claim,'operation'=>['record'=>$operationRecord,'prepared'=>$op],'maximum'=>$op['maximum'],'settled'=>null,'custody'=>[]];
                    $s['source_fences'][$ck]=$this->store->make('imperium.bootstrap-source-fence/v1','fence-'.substr($ck,7,24),['budget_identity'=>$budget['identity'],'source_identity'=>R::hash([$budget['identity'],$op['authority_source']]),'command_ref'=>$command['ref'],'claim_ref'=>R::reference($claim),'reason'=>'RESERVED_OUTCOME_UNCERTAIN','predecessor_head'=>$head]);
                }
                $s['sequences'][$sk]['head']=$command['ref'];
            }
            $s['commands'][$key]=$command;
            if($q['mode']==='advance'){LedgerState::validate($s);$state['onboarding']=$s;}
            return self::presentation($command,$head,false,$q['mode']==='preview');
        });
    }
    public function policy(array $s,array $ref): array {
        $matches=array_values(array_filter($s['policies'],static fn(array $v):bool=>$v['record']['id']===$ref['id'] && $v['record']['record_digest']===$ref['digest'] && $v['record']['body']['policy_version']===$ref['version']));
        R::require(count($matches)===1,'POLICY_SOURCE');return $this->store->checkSource($s,R::reference($matches[0]['record']));
    }
    public function authority(array $s,array $policy,array $step): array {
        $slots=array_values(array_filter($policy['body']['effect_slots'],static fn(array $v):bool=>$v['slot_id']===$step['effect_slot_id']));R::require(count($slots)===1,'SLOT_MISSING');$slot=$slots[0];
         $derived=$slot['terms_rule']['kind']==='exact'?['terms_ref'=>$slot['terms_rule']['object_ref'],'derivation_input_refs'=>[]]:\App\Imperium\Runtime\Onboarding\Augur\FoundingRule::derive($s,$policy,$slot,fn(array $ref):array=>$this->store->checkSource($s,$ref));$terms=$derived['terms_ref'];$found=[];
        foreach($s['admissions'] as $key=>$receipt){$a=Admission::retained($s,$key);$p=$a['envelope']['payload'];
            if($slot['authority_mode']==='policy_effect' && $p['effect']==='AUTHORIZE_BOOTSTRAP_POLICY' && R::same(R::reference($a['object']),R::reference($policy))){$found[]=['kind'=>'policy_effect','policy_ref'=>R::reference($policy),'policy_admission_ref'=>R::reference($receipt),'slot_id'=>$slot['slot_id'],'slot_digest'=>R::hash($slot),'terms_ref'=>$terms,'derivation_input_refs'=>$derived['derivation_input_refs']];}
            elseif($slot['authority_mode']==='signed_act' && $p['effect']===$slot['effect'] && R::same($p['policy_ref'],R::reference($policy)) && R::same(R::reference($a['object']),$terms)){$found[]=['kind'=>'signed_act','act_ref'=>R::reference($a['record']),'admission_ref'=>R::reference($receipt)];}
        }
        R::require(count($found)===1,'AUTHORITY_ORIGINAL_MISSING');return [$found[0],$slot,CurrentAuthority::verify($this->store,$s,$found[0],$slot['effect'],$terms)];
    }
    public function operation(array $state,array $policy,string $effect,array $terms,array $op): void {
        R::object($op,['schema','wire','destination','method','provider','model','configuration_ref','credential_operation','adapter','maximum','expires_at','authority_source']);
        R::require($op['schema']==='imperium.bootstrap-prepared-operation/v1' && is_string($op['wire']) && strlen($op['wire'])<=1048576 && $op['provider']==='deepseek','PREPARED_OPERATION');
        SharedExposure::meters($op['maximum']);R::require($op['expires_at']>$this->store->now() && $op['expires_at']<=$policy['body']['expires_at'],'LEASE_TIME');
        $access=$effect==='AUTHORIZE_BOOTSTRAP_ACCESS';$src=$op['authority_source'];
        R::object($src,$access?['kind','grant_ref','executor']:['kind','commission_ref','holder_ref']);R::require($src['kind']===($access?'access':'assessment'),'AUTHORITY_SOURCE');
        if($access){R::ref($src['grant_ref']);R::object($src['executor'],['kind','adapter_ref','deployment_custody_ref','credential_binding_ref']);R::require($src['executor']['kind']==='infrastructure','EXECUTOR');foreach(['adapter_ref','deployment_custody_ref','credential_binding_ref'] as $f){R::ref($src['executor'][$f]);}}
        else{R::ref($src['commission_ref']);R::ref($src['holder_ref']);}
        R::require($op['destination']===($access?'https://api.deepseek.com:443/models':'https://api.deepseek.com:443/chat/completions') && $op['method']===($access?'GET':'POST'),'DESTINATION');
        R::require($op['maximum']['cost_microusd']<=($access?0:100000) && $op['maximum']['milliseconds']<=($access?10000:60000) && $op['maximum']['input_tokens']<=($access?0:16384) && $op['maximum']['output_tokens']<=($access?0:4096),'RESOURCE_SCOPE');
        $this->sources->verify($this->store,$state,$policy,$effect,$terms,$op);
    }
    public function validateResponse(array $state,array $claim,array $envelope):string{
        $s=$this->store->state($state);$cmd=LedgerState::command($s,$claim['record']['body']['command_ref']);$policy=$this->policy($s,$cmd['request']['policy_ref']);
        $effect=$claim['record']['body']['authority_source']['kind']==='access'?'AUTHORIZE_BOOTSTRAP_ACCESS':'AUTHORIZE_BOOTSTRAP_ASSESSMENT';
        $classification=$this->sources->validateResponse($this->store,$state,$policy,$effect,$claim['operation']['prepared'],$envelope);R::require(in_array($classification,['SUCCEEDED','TERMINAL_FAILURE'],true),'RETRY_REASON_NOT_ADMITTED');return $classification;
    }
    public function complete(array $commandRef,array $consumption,?string $effect,array $results):array{return $this->store->make('imperium.bootstrap-step-completion/v1','complete-'.substr(R::hash($commandRef),7,24),['command_ref'=>$commandRef,'step_consumption_ref'=>R::reference($consumption),'effect'=>$effect,'result_refs'=>R::refs($results),'completed_at'=>$this->store->now()],[R::reference($consumption)]);}
    private function command(array $q,string $raw,array $head,array $policy,string $status):array{
        $result=['sequence_id'=>$q['sequence_id'],'command_id'=>$q['command_id'],'request_digest'=>R::hash($q),'step_id'=>$q['step_id'],'predecessor_ref'=>$q['predecessor_ref'],'policy_ref'=>R::reference($policy),'budget_ref'=>$policy['body']['budget_ref'],'observed_head'=>$head,'admission_status'=>$status];
        return ['key'=>[$this->store->instance,$q['sequence_id'],$q['command_id']],'request'=>$q,'raw_digest'=>'sha256:'.hash('sha256',$raw),'result'=>$result,'ref'=>['sequence_id'=>$q['sequence_id'],'command_id'=>$q['command_id'],'request_digest'=>R::hash($q),'result_digest'=>R::hash($result)]];
    }
    public static function presentation(array $c,array $head,bool $historical,bool $preview=false):array{return ['schema'=>'imperium.provider-onboarding-status/v2','sequence_id'=>$c['ref']['sequence_id'],'command_id'=>$c['ref']['command_id'],'mode'=>$preview?'preview':'advance','status'=>$historical?'HISTORICAL_RECOGNITION':($preview?'PREVIEW':$c['result']['admission_status']),'reason_codes'=>[],'head'=>['generation'=>$head['generation'],'digest'=>$head['digest']===null?null:'sha256:'.$head['digest']],'sequence_head'=>$c['ref'],'result_ref'=>$preview?null:$c['ref'],'facts'=>['historical'=>$historical],'next_action'=>['code'=>'READ_STATUS','step_id'=>null,'explanation'=>'Use retained state; no automatic dispatch.'],'evidence_refs'=>[],'effects'=>['new_effects_this_command'=>!$historical&&!$preview],'operational_flags'=>['deployment_approved'=>false,'enrollment_authorized'=>false,'live_ready'=>false,'activation'=>false,'execution_authority'=>false]];}
}
