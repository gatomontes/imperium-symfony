<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Ledger;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules as R,Admission,Act,Policy,StrictJson};
use App\Imperium\Runtime\Citadel\Formation\SharedExposure;

/** Structural/history validation only: no clock, external ports, locks or execution permission. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class StateValidation
{
    private array $records=[];
    private array $policySteps=[];
    private array $policySlots=[];
    public function __construct(private array $s) {}
    private function equal(mixed $a,mixed $b,string $code='STATE_LINK'):void {R::require(R::same($a,$b),$code);}
    private function graphId(mixed $v):void {R::require(is_string($v) && preg_match('/\A[a-z0-9][a-z0-9._-]{0,79}\z/',$v)===1,'GRAPH_ID');}
    private function list(mixed $v,int $max=256):array {R::require(is_array($v) && array_is_list($v) && count($v)<=$max,'STATE_LIST');return $v;}
    private function tuple(mixed $v,int $n):array {$v=$this->list($v,$n);R::require(count($v)===$n,'STATE_TUPLE');return $v;}
    private function refs(mixed $v,bool $sorted=true):array {$v=$this->list($v);$refs=R::refs($v);if($sorted){$this->equal($refs,$v,'SOURCE_ORDER');}return $v;}
    private function identity(array $h):void {$this->equal([$h['instance_id'],$h['citadel_id']],[$this->s['trust']['instance_id'],$this->s['trust']['citadel_id']],'FOREIGN_RECORD');}
    private function h(mixed $v,string $schema):array {$h=R::record($v);$this->identity($h);R::require($h['schema']==='imperium.bootstrap-'.$schema.'/v1' && $h['producer']['service']==='onboarding.authority-admission','LEDGER_SCHEMA');return $h;}
    private function sources(array $h,array $refs):void {$this->equal($h['sources'],R::refs($refs),'RECORD_SOURCES');}
    private function retainRecord(array $h):void {$key=R::key(R::reference($h));if(isset($this->records[$key])){$this->equal($this->records[$key],$h);}else{$this->records[$key]=$h;}}
    private function original(mixed $ref):array {$ref=R::ref($ref);$h=$this->records[R::key($ref)]??null;R::require(is_array($h),'STATE_ORIGINAL_MISSING');return $h;}
    private function policy(mixed $ref):array {$h=$this->original($ref);R::require($h['schema']==='imperium.operator-bootstrap-policy/v1','STATE_POLICY');return $h;}
    private function step(array $policy,mixed $id):array {$this->graphId($id);$step=$this->policySteps[$policy['record_digest']][$id]??null;R::require(is_array($step),'STATE_STEP');return $step;}
    private function slot(array $policy,mixed $id):array {$this->graphId($id);$slot=$this->policySlots[$policy['record_digest']][$id]??null;R::require(is_array($slot),'STATE_SLOT');return $slot;}
    private function sequenceRef(mixed $v,array $command,array $policy):void {$this->equal(R::object($v,['instance_id','sequence_id','policy_digest']),['instance_id'=>$this->s['trust']['instance_id'],'sequence_id'=>$command['ref']['sequence_id'],'policy_digest'=>$policy['record_digest']],'SEQUENCE_REF');}
    private function command(mixed $ref):array {LedgerState::commandRef($ref);return LedgerState::command($this->s,$ref);}

    public function run():void
    {
        $s=$this->s;
        R::object($s,['schema','trust','acts','policies','evidence','revocations','admissions','migration',...StateMigration::MAPS]);
        R::require($s['schema']==='imperium.onboarding-authority-state/v2','STATE_VERSION');R::record($s['trust']);
        $count=0;
        foreach(['acts','policies','evidence','revocations','admissions',...StateMigration::MAPS] as $map){
            R::require(is_array($s[$map]) && ($s[$map]===[] || !array_is_list($s[$map])) && count($s[$map])<=8192,'STATE_MAP');
            foreach(array_keys($s[$map]) as $key){R::require(is_string($key),'STATE_MAP_KEY');}
            if(in_array($map,StateMigration::MAPS,true)){$count+=count($s[$map]);}
        }
        R::require($count<=8192 && count($s['commands'])<=4096 && count($s['sequences'])<=256 && count($s['admissions'])<=4096 && count($s['policies'])+count($s['evidence'])<=8192,'LIMIT_EXCEEDED');
        foreach(['bindings','applications','assessment_views'] as $map){R::require($s[$map]===[],'UNSUPPORTED_STATE_PRODUCER');}
        foreach(['commands'=>['key','request','raw_digest','result','ref'],'sequences'=>['key','registration','head'],'steps'=>['key','consumption','completion'],'slots'=>['key','command_ref','authority_key'],'claims'=>['key','record','operation','maximum','settled','custody'],'budget_bindings'=>['key','record']] as $map=>$fields){foreach($s[$map] as $entry){R::object($entry,$fields);}}
        foreach(['sequences'=>'registration','steps'=>'consumption','claims'=>'record','budget_bindings'=>'record'] as $map=>$field){foreach($s[$map] as $entry){R::record($entry[$field]);}}
        foreach($s['commands'] as $entry){R::object($entry['result'],['sequence_id','command_id','request_digest','step_id','predecessor_ref','policy_ref','budget_ref','observed_head','admission_status']);LedgerState::commandRef($entry['ref']);}
        foreach($s['claims'] as $entry){R::object($entry['operation'],['record','prepared']);R::object($entry['operation']['prepared'],['schema','wire','destination','method','provider','model','configuration_ref','credential_operation','adapter','maximum','expires_at','authority_source']);}
        $this->baseAndMigration();
        $newRecords=[];
        $walk=function(mixed $v,int $depth=0)use(&$walk,&$newRecords):void{
            R::require($depth<=16,'STATE_DEPTH');if(!is_array($v)){return;}
            if(isset($v['record_digest'],$v['citadel_id'])){
                $h=R::record($v);$this->identity($h);
                $fields=match($h['schema']){
                    'imperium.bootstrap-sequence-registration/v1'=>['sequence_ref','policy_ref','budget_ref','initial_evidence_refs','registration_command_ref'],
                    'imperium.bootstrap-step-consumption/v1'=>['sequence_ref','command_ref','policy_ref','step_id','slot_id','authority_consumption','prepared_operation_ref','predecessor_head'],
                    'imperium.bootstrap-step-completion/v1'=>['command_ref','step_consumption_ref','effect','result_refs','completed_at'],
                    'imperium.bootstrap-cognition-claim/v1'=>['sequence_ref','command_ref','policy_ref','authority_source','prepared_operation_ref','budget_ref','authority_consumption','lease_consumption','custody_checkpoint','response_ref'],
                    'imperium.bootstrap-prepared-operation-record/v1'=>['operation'],
                    'imperium.bootstrap-custody-checkpoint/v1'=>['claim_ref','operation_digest','stage','previous_ref','response_metadata','response_envelope'],
                    'imperium.bootstrap-budget-binding/v1'=>['budget_ref','budget_identity','limit_ref','source_bindings','predecessor_head'],
                    'imperium.bootstrap-source-fence/v1'=>['budget_identity','source_identity','command_ref','claim_ref','reason','predecessor_head'],
                    'imperium.bootstrap-group-input/v1'=>['policy_ref','group_id','holder_ref','configuration_ref','workload_ref','resolved_input_refs','semantic_input_digest'],
                    default=>throw new \RuntimeException('O2_LEDGER_SCHEMA'),
                };
                R::object($h['body'],$fields);$this->retainRecord($h);$newRecords[R::key(R::reference($h))]=true;R::require(count($newRecords)<=8192,'LIMIT_EXCEEDED');
            }
            foreach($v as $child){$walk($child,$depth+1);}
        };
        foreach(StateMigration::MAPS as $map){$walk($s[$map]);}
        foreach($s['attempt_outcomes'] as $outcome){R::require(is_array($outcome),'OUTCOME_SHAPE');$plain=$outcome;unset($plain['record_digest']);R::require(($outcome['schema']??null)==='imperium.bootstrap-attempt-outcome/v1' && ($outcome['record_digest']??null)===R::hash($plain),'OUTCOME_SCHEMA');$this->retainRecord($outcome);}
        foreach($s['policies'] as $v){$p=$v['record'];Policy::validate($p,fn(array $ref):array=>$this->original($ref));foreach($p['body']['steps'] as $step){$this->policySteps[$p['record_digest']][$step['step_id']]=$step;}foreach($p['body']['effect_slots'] as $slot){$this->policySlots[$p['record_digest']][$slot['slot_id']]=$slot;}}
        $this->commandsAndSequences();$this->stepsAndSlots();$this->budgets();$this->claimsAndFences();$this->groupsAndOutcomes();
    }

    private function baseAndMigration():void
    {
        $s=$this->s;$trust=$s['trust'];R::require($trust['schema']==='imperium.bootstrap-trust/v1','TRUST_SCHEMA');
        $t=R::object($trust['body'],['public_key','fingerprint','issuer','competence','effects','not_before','expires_at','enrollment_receipt_ref']);
        $issuer=R::object($t['issuer'],['kind','id']);R::id($issuer['id']);R::require($issuer['kind']==='operator' && $t['competence']==='OPERATOR_BOOTSTRAP_POLICY','ENROLLMENT_COMPETENCE');
        R::require($t['fingerprint']==='sha256:'.hash('sha256',R::bytes($t['public_key'],32)),'TRUST_FINGERPRINT');R::time($t['not_before']);R::time($t['expires_at']);R::require($t['not_before']<$t['expires_at'],'TRUST_TIME');
        $effects=$this->list($t['effects']);R::require(count($effects)===count(array_unique($effects)),'ENROLLMENT_EFFECTS');foreach($effects as $effect){R::effect($effect,'signed_act');}
        foreach(['evidence','policies'] as $map){foreach($s[$map] as $key=>$v){
            R::object($v,['record','raw','admission_key']);$h=R::record($v['record']);$this->identity($h);R::require($key===R::key(R::reference($h)) && is_string($v['raw']) && strlen($v['raw'])<=4194304,'SOURCE_KEY');$this->equal(StrictJson::decode($v['raw']),$h,'SOURCE_BYTES');
            if($v['admission_key']===null){R::require($h['schema']==='imperium.bootstrap-enrollment/v1','UNSIGNED_SOURCE');}else{R::digest($v['admission_key']);R::require(isset($s['admissions'][$v['admission_key']]),'ADMISSION_MISSING');}
            R::require(($h['schema']==='imperium.operator-bootstrap-policy/v1')===($map==='policies'),'SOURCE_MAP');$this->retainRecord($h);
        }}
        $enrollment=$this->original($t['enrollment_receipt_ref']);R::require($enrollment['schema']==='imperium.bootstrap-enrollment/v1','ENROLLMENT_SOURCE');$b=R::object($enrollment['body'],['public_key','fingerprint','issuer','competence','effects','not_before','expires_at','custody_receipt']);R::text($b['custody_receipt']);
        foreach(['public_key','fingerprint','issuer','competence','effects','not_before','expires_at'] as $field){$this->equal($b[$field],$t[$field],'ENROLLMENT_MISMATCH');}$this->sources($trust,[R::reference($enrollment)]);$this->sources($enrollment,[]);
        R::require(count($s['acts'])===count($s['admissions']),'ADMISSION_MAP');
        foreach($s['acts'] as $key=>$v){
            R::object($v,['record','envelope','object','raw_envelope','raw_object','fingerprint']);R::digest($key);R::digest($v['fingerprint']);$a=Admission::retained($s,$key);$payload=Act::shape($a['envelope'])['payload'];
            R::require($key===R::hash([$payload['instance_id'],$payload['trust_fingerprint'],$payload['nonce']]),'ADMISSION_KEY');
            $this->equal([$payload['instance_id'],$payload['citadel_id'],$payload['trust_fingerprint']],[$trust['instance_id'],$trust['citadel_id'],$t['fingerprint']],'ACT_IDENTITY');
            $this->identity(R::record($a['object']));$this->equal($this->original(R::reference($a['object'])),$a['object']);
            $act=$this->h($a['record'],'retained-act');R::object($act['body'],['envelope','object_ref','raw_envelope_sha256','raw_object_sha256']);$this->sources($act,[R::reference($a['object'])]);
            $receipt=$this->h($s['admissions'][$key],'original-admission');$rb=R::object($receipt['body'],['act_ref','object_ref','predecessor_head','authority_consumed','effect_completed']);R::head($rb['predecessor_head']);$this->equal($rb['predecessor_head'],$payload['expected_head'],'ADMISSION_HEAD');$this->sources($receipt,[R::reference($act),R::reference($a['object'])]);$this->retainRecord($act);$this->retainRecord($receipt);
        }
        foreach($s['revocations'] as $key=>$ref){$h=$this->original($ref);R::require($h['schema']==='imperium.bootstrap-revocation/v1','REVOCATION_SCHEMA');$b=R::object($h['body'],['target_kind','target_id','expected_head']);R::require(in_array($b['target_kind'],['issuer','act','policy'],true) && $key===$b['target_kind'].':'.$b['target_id'],'REVOCATION_KEY');R::text($b['target_id']);R::head($b['expected_head']);}
        $migration=$this->h($s['migration'],'state-migration');$b=R::object($migration['body'],['from_schema','to_schema','predecessor_head','prior_subtree_digest','preserved_maps_digest']);
        R::require($migration['created_at']>=$t['not_before'] && $migration['created_at']<$t['expires_at'],'MIGRATION_TIME');
        R::require($b['from_schema']==='imperium.onboarding-authority-state/v1' && $b['to_schema']===$s['schema'],'STATE_MIGRATION');R::head($b['predecessor_head']);R::require($b['predecessor_head']['generation']>0,'MIGRATION_HEAD');R::digest($b['prior_subtree_digest']);R::digest($b['preserved_maps_digest']);$this->sources($migration,[$t['enrollment_receipt_ref']]);
        // Original admissions are immutable and retain the first publication predecessor.
        $prior=['schema'=>$b['from_schema'],'trust'=>$trust,'acts'=>[],'policies'=>[],'evidence'=>[],'revocations'=>[],'admissions'=>[]];
        foreach($s['admissions'] as $key=>$receipt){if($receipt['body']['predecessor_head']['generation']<$b['predecessor_head']['generation']){$prior['admissions'][$key]=$receipt;$prior['acts'][$key]=$s['acts'][$key];}}
        foreach(['policies','evidence'] as $map){foreach($s[$map] as $key=>$v){if($v['admission_key']===null || isset($prior['admissions'][$v['admission_key']])){$prior[$map][$key]=$v;}}}
        foreach($s['revocations'] as $key=>$ref){if(isset($prior['evidence'][R::key($ref)])){$prior['revocations'][$key]=$ref;}}
        R::require(R::hash($prior)===$b['prior_subtree_digest'],'MIGRATION_HISTORY');unset($prior['schema']);R::require(R::hash($prior)===$b['preserved_maps_digest'],'MIGRATION_HISTORY');
    }

    private function commandsAndSequences():void
    {
        $s=$this->s;$instance=$s['trust']['instance_id'];
        foreach($s['commands'] as $key=>$c){
            R::object($c,['key','request','raw_digest','result','ref']);$this->tuple($c['key'],3);R::digest($c['raw_digest']);LedgerState::commandRef($c['ref']);
            $q=$c['request'];R::require(is_array($q),'COMMAND_REQUEST');
            if(($q['schema']??null)==='imperium.provider-onboarding-resume/v2'){Recovery::request(json_encode($q,JSON_THROW_ON_ERROR));}else{CommandLedger::request(json_encode($q,JSON_THROW_ON_ERROR));R::require($q['mode']==='advance' && $q['instance_id']===$instance,'COMMAND_MODE');}
            $this->equal($c['key'],[$instance,$q['sequence_id'],$q['command_id']],'COMMAND_KEY');R::require($key===LedgerState::key('command',$c['key']),'COMMAND_KEY');
            $result=R::object($c['result'],['sequence_id','command_id','request_digest','step_id','predecessor_ref','policy_ref','budget_ref','observed_head','admission_status']);
            $policy=$this->policy($result['policy_ref']);$this->equal($result['budget_ref'],$policy['body']['budget_ref']);R::head($result['observed_head']);$this->equal($result['observed_head'],CommandLedger::head($q['expected_head']),'COMMAND_HEAD');
            if($result['step_id']!==null){$this->step($policy,$result['step_id']);}if($result['predecessor_ref']!==null){LedgerState::commandRef($result['predecessor_ref']);}
            $this->equal($c['ref'],['sequence_id'=>$q['sequence_id'],'command_id'=>$q['command_id'],'request_digest'=>R::hash($q),'result_digest'=>R::hash($result)],'COMMAND_DIGEST');
            $this->equal([$result['sequence_id'],$result['command_id'],$result['request_digest']],[$q['sequence_id'],$q['command_id'],R::hash($q)],'COMMAND_IDENTITY');
            if($q['schema']==='imperium.provider-onboarding-resume/v2'){
                $original=$this->command($q['recognize_command_ref']);R::require($original['request']['schema']==='imperium.provider-onboarding-request/v2' && $original['ref']['sequence_id']===$q['sequence_id'],'RESUME_SOURCE');
                $expected=$original['result'];$expected['command_id']=$q['command_id'];$expected['request_digest']=R::hash($q);$expected['observed_head']=CommandLedger::head($q['expected_head']);$expected['admission_status']='EVIDENCE_RECOGNITION';$this->equal($result,$expected,'RESUME_RESULT');
            }else{
                $this->equal($q['policy_ref'],['id'=>$policy['id'],'version'=>$policy['body']['policy_version'],'digest'=>$policy['record_digest']],'COMMAND_POLICY');
                $this->equal([$result['step_id'],$result['predecessor_ref'],$result['admission_status']],[$q['step_id'],$q['predecessor_ref'],$q['step_id']===null?'REGISTERED':'STEP_ADMITTED'],'COMMAND_RESULT');
            }
            R::require(isset($s['sequences'][LedgerState::key('sequence',[$instance,$q['sequence_id']])]),'COMMAND_SEQUENCE');
        }
        $owners=[];
        foreach($s['sequences'] as $key=>$v){
            R::object($v,['key','registration','head']);$this->tuple($v['key'],2);R::id($v['key'][1]);R::require($v['key'][0]===$instance && $key===LedgerState::key('sequence',$v['key']),'SEQUENCE_KEY');
            $h=$this->h($v['registration'],'sequence-registration');$b=R::object($h['body'],['sequence_ref','policy_ref','budget_ref','initial_evidence_refs','registration_command_ref']);$this->sources($h,[]);$this->equal($b['sequence_ref'],['instance_id'=>$instance,'sequence_id'=>$v['key'][1]],'SEQUENCE_REF');
            $p=$this->policy($b['policy_ref']);R::require(!isset($owners[$p['record_digest']]),'SEQUENCE_POLICY_OWNER');$owners[$p['record_digest']]=true;$this->equal($b['budget_ref'],$p['body']['budget_ref']);$this->refs($b['initial_evidence_refs']);foreach($b['initial_evidence_refs'] as $ref){$this->original($ref);}
            $adv=[];foreach($s['commands'] as $c){if($c['request']['schema']==='imperium.provider-onboarding-request/v2' && $c['ref']['sequence_id']===$v['key'][1]){$adv[]=$c;}}
            usort($adv,static fn(array $a,array $b):int=>$a['result']['observed_head']['generation']<=>$b['result']['observed_head']['generation']);R::require($adv!==[],'REGISTRATION_MISSING');$previous=null;$generation=-1;
            foreach($adv as $i=>$c){$this->equal($c['result']['policy_ref'],$b['policy_ref']);$this->equal($c['request']['evidence_refs'],$b['initial_evidence_refs']);$this->equal($c['result']['predecessor_ref'],$previous,'SEQUENCE_PREDECESSOR');R::require($c['result']['observed_head']['generation']>$generation && ($i===0)===($c['result']['step_id']===null),'SEQUENCE_CHAIN');$generation=$c['result']['observed_head']['generation'];if($i===0){$this->equal($c['ref'],$b['registration_command_ref']);}else{$st=$s['steps'][LedgerState::key('step',[$instance,$p['record_digest'],$c['result']['step_id']])]??null;R::require(is_array($st),'STEP_ORIGINAL_MISSING');$this->equal($st['consumption']['body']['command_ref'],$c['ref']);}$previous=$c['ref'];}
            $this->equal($v['head'],$previous,'SEQUENCE_HEAD');
        }
    }

    private function authority(array $policy,array $slot):array
    {
        $found=[];
        foreach($this->s['admissions'] as $key=>$receipt){$a=$this->s['acts'][$key];$p=$a['envelope']['payload'];
            if($slot['authority_mode']==='policy_effect' && $p['effect']==='AUTHORIZE_BOOTSTRAP_POLICY' && R::same(R::reference($a['object']),R::reference($policy))){$found[]=[['kind'=>'policy_effect','policy_ref'=>R::reference($policy),'policy_admission_ref'=>R::reference($receipt),'slot_id'=>$slot['slot_id'],'slot_digest'=>R::hash($slot),'terms_ref'=>$slot['terms_rule']['object_ref'],'derivation_input_refs'=>[]],[$policy['instance_id'],$policy['record_digest'],$slot['slot_id']],$p];}
            elseif($slot['authority_mode']==='signed_act' && $p['effect']===$slot['effect'] && R::same($p['policy_ref'],R::reference($policy)) && R::same(R::reference($a['object']),$slot['terms_rule']['object_ref'])){$found[]=[['kind'=>'signed_act','act_ref'=>R::reference($a['record']),'admission_ref'=>R::reference($receipt)],[$policy['instance_id'],$p['trust_fingerprint'],$p['nonce']],$p];}
        }
        R::require(count($found)===1,'AUTHORITY_ORIGINAL_MISSING');return $found[0];
    }
    private function stepsAndSlots():void
    {
        $s=$this->s;$expectedSlots=[];$used=[];
        foreach($s['steps'] as $key=>$v){
            R::object($v,['key','consumption','completion']);$this->tuple($v['key'],3);R::require($key===LedgerState::key('step',$v['key']),'STEP_KEY');$h=$this->h($v['consumption'],'step-consumption');$this->sources($h,[]);
            $b=R::object($h['body'],['sequence_ref','command_ref','policy_ref','step_id','slot_id','authority_consumption','prepared_operation_ref','predecessor_head']);$p=$this->policy($b['policy_ref']);$step=$this->step($p,$b['step_id']);$c=$this->command($b['command_ref']);
            R::require($c['request']['schema']==='imperium.provider-onboarding-request/v2','STEP_COMMAND');$this->equal($v['key'],[$p['instance_id'],$p['record_digest'],$b['step_id']]);$this->sequenceRef($b['sequence_ref'],$c,$p);$this->equal([$c['result']['step_id'],$c['result']['policy_ref'],$b['predecessor_head']],[$b['step_id'],$b['policy_ref'],$c['result']['observed_head']],'STEP_COMMAND');$this->equal($b['slot_id'],$step['effect_slot_id']);R::head($b['predecessor_head']);
            $effect=null;
            if($b['slot_id']===null){R::require($b['authority_consumption']===null && $b['prepared_operation_ref']===null,'PURE_CONSUMPTION');}
            else{$slot=$this->slot($p,$b['slot_id']);R::require($slot['terms_rule']['kind']==='exact','UNSUPPORTED_TERMS');[$authority,$ak]=$this->authority($p,$slot);$effect=$slot['effect'];$expected=['key'=>$ak,'consumed'=>true,'commit_head'=>$b['predecessor_head']];$this->equal($b['authority_consumption'],$expected,'AUTHORITY_CONSUMPTION');$slotTuple=[$p['instance_id'],$p['record_digest'],$b['slot_id']];$expectedSlots[LedgerState::key('slot',$slotTuple)]=['key'=>$slotTuple,'command_ref'=>$c['ref'],'authority_key'=>$ak];R::require(!isset($used[R::hash($ak)]),'AUTHORITY_ALREADY_CONSUMED');$used[R::hash($ak)]=true;}
            $io=in_array($effect,['AUTHORIZE_BOOTSTRAP_ACCESS','AUTHORIZE_BOOTSTRAP_ASSESSMENT'],true);
            R::require($io===($b['prepared_operation_ref']!==null),'STEP_OPERATION');
            if($io){$this->original($b['prepared_operation_ref']);$matches=array_filter($s['claims'],static fn(array $claim):bool=>R::same($claim['record']['body']['command_ref'],$c['ref']));R::require(count($matches)===1,'STEP_CLAIM');}
            if($v['completion']!==null){$completion=$this->h($v['completion'],'step-completion');$cb=R::object($completion['body'],['command_ref','step_consumption_ref','effect','result_refs','completed_at']);$this->equal([$cb['command_ref'],$cb['step_consumption_ref'],$cb['effect']],[$c['ref'],R::reference($h),$effect],'COMPLETION_LINK');$this->sources($completion,[R::reference($h)]);R::time($cb['completed_at']);R::require($cb['completed_at']===$completion['created_at'] && $cb['completed_at']>=$h['created_at'],'COMPLETION_TIME');$this->refs($cb['result_refs']);foreach($cb['result_refs'] as $ref){$this->original($ref);}}
            else{R::require($io,'COMPLETION_MISSING');}
            if($v['completion']!==null && !$io){
                if($step['action']==='RECORD_CONFIGURATION'){$refs=[];foreach($p['body']['candidate_bindings'] as $binding){$refs[R::key($binding['configuration_ref'])]=$binding['configuration_ref'];}$this->equal($v['completion']['body']['result_refs'],R::refs(array_values($refs)),'CONFIGURATION_COMPLETION');}
                elseif(in_array($effect,['ADMIT_BOOTSTRAP_EVIDENCE','APPROVE_RUNTIME_BINDING_MAP'],true)){$terms=$this->original($slot['terms_rule']['object_ref']);$this->equal($v['completion']['body']['result_refs'],[$terms['body']['terms']],'EFFECT_COMPLETION');}
                else{R::require($step['action']==='SELECT_BASE' && $v['completion']['body']['result_refs']!==[],'UNSUPPORTED_COMPLETION');}
            }
        }
        $this->equal($s['slots'],$expectedSlots,'SLOT_LINKS');
    }

    private function budgets():void
    {
        $s=$this->s;$seen=[];$identity=null;
        foreach($s['budget_bindings'] as $key=>$v){
            R::object($v,['key','record']);$this->tuple($v['key'],2);$h=$this->h($v['record'],'budget-binding');$b=R::object($h['body'],['budget_ref','budget_identity','limit_ref','source_bindings','predecessor_head']);R::digest($b['budget_identity']);R::head($b['predecessor_head']);
            $source=$this->original($b['budget_ref']);$root=$this->original($b['limit_ref']);$body=Policy::content($source,'shared-budget');R::object($body,['schema','lineage_ref','limits','formation_sources']);$rb=Policy::content($root,'budget-root');R::object($rb,['schema','limits']);R::require($body['schema']==='imperium.bootstrap-budget-association/v1' && $rb['schema']==='imperium.bootstrap-budget-root/v1','BUDGET_SCHEMA');$this->equal($body['lineage_ref'],$b['limit_ref']);$this->equal($body['limits'],$rb['limits']);SharedExposure::meters($rb['limits'],false);
            $this->equal($b['budget_identity'],R::hash([$h['instance_id'],$h['citadel_id'],R::reference($root)]),'BUDGET_IDENTITY');if($identity!==null){$this->equal($identity,$b['budget_identity']);}$identity=$b['budget_identity'];
            $this->sources($h,[$b['budget_ref'],$b['limit_ref']]);$members=[];$unique=[];
            foreach($this->list($body['formation_sources']) as $row){R::object($row,['session_id','resource_decision_digest']);R::id($row['session_id']);R::require(is_string($row['resource_decision_digest']) && preg_match('/\A[0-9a-f]{64}\z/',$row['resource_decision_digest'])===1 && !isset($unique[$row['session_id']]),'BUDGET_MEMBER');$unique[$row['session_id']]=true;$members[]=['kind'=>'formation',...$row];}
            $this->list($b['source_bindings'],257);$last=end($b['source_bindings']);R::object($last,['kind','policy_ref']);R::require($last['kind']==='onboarding','BUDGET_MEMBER');$p=$this->policy($last['policy_ref']);$members[]=['kind'=>'onboarding','policy_ref'=>R::reference($p)];$this->equal($b['source_bindings'],$members,'BUDGET_MEMBERS');$this->equal($p['body']['budget_ref'],$b['budget_ref']);$this->equal($v['key'],[$b['budget_identity'],$p['record_digest']]);R::require($key===LedgerState::key('budget',$v['key']) && !isset($seen[$p['record_digest']]),'BUDGET_KEY');$seen[$p['record_digest']]=true;
            $sequences=array_values(array_filter($s['sequences'],static fn(array $seq):bool=>R::same($seq['registration']['body']['policy_ref'],R::reference($p))));R::require(count($sequences)===1,'BUDGET_SEQUENCE');$reg=$this->command($sequences[0]['registration']['body']['registration_command_ref']);$this->equal($b['predecessor_head'],$reg['result']['observed_head']);
        }
        R::require(count($seen)===count($s['sequences']),'BUDGET_BINDING_MISSING');
    }

    private function claimsAndFences():void
    {
        $s=$this->s;$fences=[];$commandClaims=[];
        foreach($s['claims'] as $key=>$v){
            R::object($v,['key','record','operation','maximum','settled','custody']);$this->tuple($v['key'],3);$h=$this->h($v['record'],'cognition-claim');$this->sources($h,[]);$b=R::object($h['body'],['sequence_ref','command_ref','policy_ref','authority_source','prepared_operation_ref','budget_ref','authority_consumption','lease_consumption','custody_checkpoint','response_ref']);$c=$this->command($b['command_ref']);$p=$this->policy($b['policy_ref']);$step=$this->step($p,$c['result']['step_id']);$slot=$this->slot($p,$step['effect_slot_id']);[$authority,$ak,$payload]=$this->authority($p,$slot);
            R::require(!isset($commandClaims[R::hash($c['ref'])]),'DUPLICATE_COMMAND_CLAIM');$commandClaims[R::hash($c['ref'])]=true;$this->sequenceRef($b['sequence_ref'],$c,$p);$this->equal($b['policy_ref'],$c['result']['policy_ref']);$this->equal($b['budget_ref'],$p['body']['budget_ref']);
            $cons=$s['steps'][LedgerState::key('step',[$p['instance_id'],$p['record_digest'],$step['step_id']])]['consumption'];$this->equal($b['authority_consumption'],$cons['body']['authority_consumption']);
            R::object($v['operation'],['record','prepared']);$opRecord=$this->h($v['operation']['record'],'prepared-operation-record');$this->sources($opRecord,[]);$op=$v['operation']['prepared'];$this->equal($opRecord['body'],['operation'=>$op]);$this->equal($b['prepared_operation_ref'],R::reference($opRecord));$this->equal($cons['body']['prepared_operation_ref'],R::reference($opRecord));
            R::object($op,['schema','wire','destination','method','provider','model','configuration_ref','credential_operation','adapter','maximum','expires_at','authority_source']);R::require($op['schema']==='imperium.bootstrap-prepared-operation/v1' && is_string($op['wire']) && strlen($op['wire'])<=1048576 && $op['provider']==='deepseek','PREPARED_OPERATION');foreach(['model','credential_operation','adapter','destination','method'] as $field){R::text($op[$field]);}$this->original($op['configuration_ref']);R::time($op['expires_at']);R::require($h['created_at']<$op['expires_at'] && $op['expires_at']<=min($slot['expires_at'],$payload['expires_at'],$p['body']['expires_at']),'LEASE_SCOPE');SharedExposure::meters($op['maximum']);$this->equal($v['maximum'],$op['maximum']);
            $access=$slot['effect']==='AUTHORIZE_BOOTSTRAP_ACCESS';R::require($access || $slot['effect']==='AUTHORIZE_BOOTSTRAP_ASSESSMENT','CLAIM_EFFECT');$src=R::object($op['authority_source'],$access?['kind','grant_ref','executor']:['kind','commission_ref','holder_ref']);R::require($src['kind']===($access?'access':'assessment'),'CLAIM_KIND');
            if($access){$this->original($src['grant_ref']);$executor=R::object($src['executor'],['kind','adapter_ref','deployment_custody_ref','credential_binding_ref']);R::require($executor['kind']==='infrastructure','EXECUTOR');foreach(['adapter_ref','deployment_custody_ref','credential_binding_ref'] as $field){$this->original($executor[$field]);}$lease=['kind'=>'access','grant_ref'=>$src['grant_ref'],'executor_digest'=>R::hash($src['executor'])];}
            else{$this->original($src['commission_ref']);$this->original($src['holder_ref']);$lease=['kind'=>'assessment','commission_ref'=>$src['commission_ref'],'holder_ref'=>$src['holder_ref']];}
            $this->equal($b['authority_source'],[...$src,'authority'=>$authority],'CLAIM_AUTHORITY');$this->equal($b['lease_consumption'],[...$lease,'expires_at'=>$op['expires_at'],'consumed'=>true,'commit_head'=>$c['result']['observed_head']],'CLAIM_LEASE');
            $this->equal([$op['destination'],$op['method']],$access?['https://api.deepseek.com:443/models','GET']:['https://api.deepseek.com:443/chat/completions','POST'],'DESTINATION');foreach(['input_tokens'=>$access?0:16384,'output_tokens'=>$access?0:4096,'cost_microusd'=>$access?0:100000,'milliseconds'=>$access?10000:60000] as $field=>$max){R::require($op['maximum'][$field]<=$max,'RESOURCE_SCOPE');}
            $this->equal($v['key'],[$c['ref'],$step['step_id'],R::hash($op)],'CLAIM_KEY');R::require($key===LedgerState::key('claim',$v['key']) && $b['custody_checkpoint']==='RESERVED' && $b['response_ref']===null,'IMMUTABLE_CLAIM');
            $prior=null;$time=$h['created_at'];$metadata=null;
            foreach($this->list($v['custody'],5) as $i=>$record){$record=$this->h($record,'custody-checkpoint');$cb=R::object($record['body'],['claim_ref','operation_digest','stage','previous_ref','response_metadata','response_envelope']);$this->equal([$cb['claim_ref'],$cb['operation_digest'],$cb['stage'],$cb['previous_ref']],[R::reference($h),R::hash($op),CustodyCoordinator::STAGES[$i],$prior],'CUSTODY_CHAIN');$this->sources($record,$prior===null?[R::reference($h)]:[R::reference($h),$prior]);R::require($record['created_at']>=$time && $record['created_at']<$op['expires_at'],'CUSTODY_TIME');$time=$record['created_at'];
                if($i<3){R::require($cb['response_metadata']===null && $cb['response_envelope']===null,'CUSTODY_RESPONSE_STAGE');}elseif($i===3){$metadata=ResponseEvidence::metadata($cb['response_metadata'],$v);R::require($cb['response_envelope']===null,'CUSTODY_RESPONSE_STAGE');}else{$this->equal($cb['response_metadata'],$metadata);ResponseEvidence::envelope($cb['response_envelope'],$v,$metadata);}$prior=R::reference($record);
            }
            $completion=$s['steps'][LedgerState::key('step',[$p['instance_id'],$p['record_digest'],$step['step_id']])]['completion'];
            if($v['settled']!==null){SharedExposure::meters($v['settled']);R::require(count($v['custody'])===5,'SETTLEMENT_RECEIPT');$this->equal($v['settled'],$metadata['usage'],'SETTLEMENT_USAGE');if($completion!==null){$this->equal($completion['body']['result_refs'],[$prior],'COMPLETION_RESPONSE');}else{R::require(!$access && isset($s['attempt_outcomes'][$key]) && $s['attempt_outcomes'][$key]['classification']==='TERMINAL_FAILURE','SETTLEMENT_COMPLETION');}}
            else{R::require($completion===null,'UNSETTLED_COMPLETION');$binding=array_values(array_filter($s['budget_bindings'],static fn(array $b):bool=>$b['key'][1]===$p['record_digest']))[0];$identity=$binding['record']['body']['budget_identity'];$fences[$key]=['budget_identity'=>$identity,'source_identity'=>R::hash([$identity,$op['authority_source']]),'command_ref'=>$c['ref'],'claim_ref'=>R::reference($h),'reason'=>'RESERVED_OUTCOME_UNCERTAIN','predecessor_head'=>$c['result']['observed_head']];}
        }
        R::require(count($fences)===count($s['source_fences']),'FENCE_COUNT');foreach($s['source_fences'] as $key=>$h){$h=$this->h($h,'source-fence');$this->sources($h,[]);R::require(isset($fences[$key]),'FENCE_CLAIM');$this->equal($h['body'],$fences[$key],'FENCE_LINK');}
    }

    private function groupsAndOutcomes():void
    {
        $s=$this->s;$expectedGroups=[];
        foreach($s['claims'] as $key=>$claim){if($claim['record']['body']['authority_source']['kind']!=='assessment'){continue;}$p=$this->policy($claim['record']['body']['policy_ref']);$c=$this->command($claim['record']['body']['command_ref']);$step=$this->step($p,$c['result']['step_id']);$group=AssessmentGroups::group($p,$step['run_condition']['group_id']);$op=$claim['operation']['prepared'];$refs=[];foreach($step['input_refs'] as $selector){$refs[]=$selector['kind']==='public_ref'?$selector['ref']:R::reference(AssessmentGroups::success($s,$p,$selector['group_id']));}$groupKey=LedgerState::key('group',[$p['instance_id'],$p['record_digest'],$group['group_id']]);$body=['policy_ref'=>R::reference($p),'group_id'=>$group['group_id'],'holder_ref'=>$op['authority_source']['holder_ref'],'configuration_ref'=>$op['configuration_ref'],'workload_ref'=>$group['workload_ref'],'resolved_input_refs'=>$refs,'semantic_input_digest'=>R::hash([$refs,$op['wire'],$op['model'],$op['configuration_ref'],$op['authority_source']['holder_ref']])];if(isset($expectedGroups[$groupKey])){$this->equal($expectedGroups[$groupKey],$body,'GROUP_INPUT_CHANGED');}$expectedGroups[$groupKey]=$body;
            if($claim['settled']!==null){R::require(isset($s['attempt_outcomes'][$key]),'OUTCOME_MISSING');}
        }
        R::require(count($s['group_inputs'])===count($expectedGroups),'GROUP_COUNT');foreach($s['group_inputs'] as $key=>$h){$h=$this->h($h,'group-input');$this->sources($h,[]);R::require(isset($expectedGroups[$key]),'GROUP_KEY');$this->equal($h['body'],$expectedGroups[$key],'GROUP_INPUT');$this->refs($h['body']['resolved_input_refs'],false);foreach([$h['body']['holder_ref'],$h['body']['configuration_ref'],$h['body']['workload_ref'],...$h['body']['resolved_input_refs']] as $ref){$this->original($ref);}}
        $seen=[];
        foreach($s['attempt_outcomes'] as $key=>$o){R::object($o,['schema','id','instance_id','sequence_ref','group_id','attempt_step_id','claim_ref','response_refs','classification','reason_code','evidence_refs','usage_ref','retry_policy_ref','observed_at','record_digest']);R::id($o['id']);R::time($o['observed_at']);R::digest($o['record_digest']);$plain=$o;unset($plain['record_digest']);R::require($o['schema']==='imperium.bootstrap-attempt-outcome/v1' && R::hash($plain)===$o['record_digest'] && in_array($o['classification'],['SUCCEEDED','TERMINAL_FAILURE'],true),'OUTCOME_SCHEMA');$claim=$s['claims'][$key]??null;R::require(is_array($claim) && $claim['settled']!==null && $claim['record']['body']['authority_source']['kind']==='assessment','OUTCOME_CLAIM');$p=$this->policy($claim['record']['body']['policy_ref']);$c=$this->command($claim['record']['body']['command_ref']);$step=$this->step($p,$c['result']['step_id']);$last=$claim['custody'][4];$ref=R::reference($last);$this->equal([$o['instance_id'],$o['sequence_ref'],$o['group_id'],$o['attempt_step_id'],$o['claim_ref'],$o['response_refs'],$o['evidence_refs'],$o['usage_ref'],$o['retry_policy_ref'],$o['reason_code']],[$p['instance_id'],$claim['record']['body']['sequence_ref'],$step['run_condition']['group_id'],$step['step_id'],R::reference($claim['record']),[$ref],[$ref],$ref,R::reference($p),$o['classification']==='SUCCEEDED'?'VALIDATED_RESULT':'FINAL_NONCONFORMING_RESULT'],'OUTCOME_LINK');R::require($o['observed_at']>=$last['created_at'],'OUTCOME_TIME');$completed=$s['steps'][LedgerState::key('step',[$p['instance_id'],$p['record_digest'],$step['step_id']])]['completion'];R::require(($completed!==null)===($o['classification']==='SUCCEEDED'),'OUTCOME_COMPLETION');$identity=R::hash([$p['record_digest'],$step['step_id']]);R::require(!isset($seen[$identity]),'OUTCOME_CONFLICT');$seen[$identity]=true;}
    }
}
