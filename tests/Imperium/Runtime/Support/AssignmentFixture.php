<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;
use App\Imperium\Runtime\Onboarding\Augur\{AugurAdapter,AugurMigration,CognitionEvidence,CognitionResources};
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules as R,Policy};
use App\Imperium\Runtime\Onboarding\DeepSeek\{Runtime,TokenEvidence,Tariff};
use App\Imperium\Runtime\Onboarding\ResponseValidation\{ParsedResponse,CandidateClaim};
use Symfony\Component\HttpClient\{MockHttpClient,Response\MockResponse};

/** An explicitly synthetic provider protocol, never a production tokenizer or entitlement. */
final class AssignmentFixture
{
    public AugurFreshFixture $fresh;
    public AugurAdapter $adapter;
    public array $requests=[];
    public array $wires=[];
    public array $assignmentSets=[];
    public array $assignmentPins=[];
    public bool $available=true;
    public int $profileGeneration=1;
    public int $bindingGeneration=1;
    public function __construct(public string $mode='valid',public string $applicationMode='A')
    {
        $pins=[];
        $this->fresh=new AugurFreshFixture(configure:function(array &$p,OnboardingAuthorityFixture $f,array $profile)use(&$pins):void{
            $constitution=null;foreach($f->sources as $h){if(($h['body']['kind']??null)==='augur-constitution'){$constitution=R::reference($h);}}
            foreach($p['body']['targets'] as &$target){$target['profile_ref']=$f->source('synthetic-role-profile',['role'=>$target['role'],'predicate_ids'=>$target['predicate_ids']]);}unset($target);
            $account=$f->source('synthetic-cognition-account',['credential_ref'=>$p['body']['credential_ref'],'account_scope'=>'synthetic-account','methods'=>['POST'],'models'=>['deepseek-v4-flash','deepseek-v4-pro'],'not_before'=>$f->now-1,'expires_at'=>$f->now+1800]);
            $token=$f->source('synthetic-cognition-tokenizer',['algorithm'=>'fixed-four-octet-blocks/v1','frame_tokens'=>8,'context_tokens'=>32768,'generated_tokens'=>4096]);
            $tariff=$f->source('synthetic-cognition-tariff',['account_scope'=>'synthetic-account','model'=>'deepseek-v4-flash','miss_rate'=>440000,'hit_rate'=>44000,'output_rate'=>1320000,'rounding'=>'ceil_each_meter_microusd','fees'=>0,'not_before'=>$f->now-1,'expires_at'=>$f->now+1800]);
            $predicates=array_fill_keys(CandidateClaim::PREDICATES,'PASS');if($this->mode==='no-fit'){$predicates['exact_role_profile_fit']='FAIL';}
            $findings=$f->source('synthetic-cognition-findings',['models'=>array_column($p['body']['candidate_bindings'],'binding_ref'),
                'profiles'=>[$profile,...array_column($p['body']['targets'],'profile_ref')],'capacity_tiers'=>in_array($this->mode,['no-fit','unknown'],true)?[]:($this->mode==='ranked'?array_map(static fn(array $b):array=>[$b['binding_ref']],$p['body']['candidate_bindings']):[array_column($p['body']['candidate_bindings'],'binding_ref')]),
                'predicates'=>$predicates,'fit'=>$this->mode==='no-fit'?'NOT_FIT':'FIT']);
            foreach([$account,$token,$tariff,$findings] as $ref){$pins[R::key($ref)]=$f->sources[R::key($ref)];}
            foreach($p['body']['effect_slots'] as &$slot){if($slot['effect']!=='AUTHORIZE_BOOTSTRAP_ASSESSMENT'){continue;}
                $group=strtoupper(substr($slot['slot_id'],5,2));$commission=$f->source('augur-assessment-commission',[
                    'schema'=>'imperium.augur-assessment-commission/v1','group_id'=>$group,'founding_step_id'=>'found-augur','constitution_ref'=>$this->mode==='wrong-constitution'?$profile:$constitution,
                    'profile_ref'=>match($group){'W1'=>$profile,'W2'=>$p['body']['targets'][0]['profile_ref'],'W3'=>$p['body']['targets'][1]['profile_ref']},
                    'account_scope'=>'synthetic-account','account_ref'=>$account,'token_ref'=>$token,'tariff_ref'=>$tariff,'evidence_refs'=>[$findings],
                    'not_before'=>$f->now-1,'expires_at'=>$this->mode==='expired-commission'?$f->now-1:$f->now+1200]);
                $old=$slot['terms_rule']['object_ref'];$terms=$f->sources[R::key($old)];unset($f->sources[R::key($old)],$terms['record_digest']);
                $terms['body']['terms']=$commission;$terms['sources']=[$commission];$terms=R::seal($terms);$ref=R::reference($terms);$f->sources[R::key($ref)]=$terms;$slot['terms_rule']['object_ref']=$ref;
            }unset($slot);
            foreach($p['body']['steps'] as &$step){if(!str_starts_with($step['step_id'],'w')){continue;}
                foreach($step['input_refs'] as $i=>&$selector){if($selector['kind']==='public_ref'){$selector['ref']=$f->source('synthetic-assessment-input',['step_id'=>$step['step_id'],'input_index'=>$i,'evidence_ref'=>$findings]);}}unset($selector);
            }unset($step);
            $this->configureAssignments($p,$f);
        },constitutionLifetime:$this->mode==='expired-holder'?200:1800);
        $d=$this->fresh->d;
        $verifier=new SyntheticAssignmentCognitionEvidence($pins,$d->policy['body']['credential_ref']);
        $this->adapter=$this->mode==='missing-evidence'?new AugurAdapter($d->adapter,$this->fresh->founding(),$d->keys):new AugurAdapter($d->adapter,$this->fresh->founding(),$d->keys,$verifier);
        $d->runtime=new Runtime($d->f->store,$this->adapter,$d->keys,$d->envelopes,new MockHttpClient(function(string $method,string $url,array $options):MockResponse{
            $auth=array_values(array_filter($options['headers'],static fn(string $h):bool=>str_starts_with(strtolower($h),'authorization:')));
            R::require(count($auth)===1 && str_starts_with($auth[0],'Authorization: Bearer synthetic-'),'SYNTHETIC_MOCK_AUTH');
            $this->requests[]=['method'=>$method,'url'=>$url];
            if($method==='GET'){return new MockResponse(DeepSeekFixture::listing());}
            R::require($method==='POST' && $url==='https://api.deepseek.com/chat/completions' && $options['max_redirects']===0,'SYNTHETIC_MOCK_DESTINATION');
            $wire=$options['body'];$this->wires[]=$wire;$v=json_decode($wire,true,512,JSON_THROW_ON_ERROR);$user=json_decode($v['messages'][1]['content'],true,512,JSON_THROW_ON_ERROR);$input=$user['input'];
            $evidence=array_map(R::reference(...),$input['evidence']);$rows=[];
                foreach($input['candidates'] as $candidate){$predicates=[];foreach(CandidateClaim::PREDICATES as $name){$predicates[$name]=['disposition'=>$this->mode==='no-fit' && $name==='exact_role_profile_fit'?'FAIL':'PASS','evidence_refs'=>$evidence];}
                $rows[]=['binding_ref'=>$candidate['binding_ref'],'predicates'=>$predicates,'fit'=>$this->mode==='no-fit'?'NOT_FIT':'FIT','evidence_refs'=>$evidence,'limitations'=>[]];}
            $body=['schema'=>$input['group_id']==='W1'?'imperium.bootstrap-assessment-result/v1':'imperium.bootstrap-role-assessment-response/v2','group_id'=>$input['group_id'],'input_digest'=>$user['input_digest'],
                'holder_ref'=>$input['holder']['ref'],'profile_ref'=>R::reference($input['profile']),'candidate_rows'=>$rows,'contradictions'=>[],'unknowns'=>[],'evidence_refs'=>$evidence];
            if($input['group_id']!=='W1'){$body['capacity_order']=in_array($this->mode,['no-fit','unknown'],true)?['kind'=>'unknown','reason'=>'No verified order','evidence_refs'=>$evidence]:['kind'=>'ranked','tiers'=>$this->mode==='ranked'?array_map(static fn(array $b):array=>['binding_refs'=>[$b['binding_ref']],'rationale'=>'Synthetic ordered capacity','evidence_refs'=>$evidence],$input['candidates']):[['binding_refs'=>array_column($input['candidates'],'binding_ref'),'rationale'=>'Synthetic equal capacity','evidence_refs'=>$evidence]]];}
            if($this->mode==='invalid'){$body['input_digest']='sha256:'.str_repeat('0',64);}
            $content=OnboardingAuthorityFixture::json($body);$prompt=count(str_split($wire,4))+8;$generated=count(str_split($content,4));
            $chat=['id'=>'synthetic-'.$input['group_id'],'object'=>'chat.completion','created'=>1,'model'=>$v['model'],'system_fingerprint'=>'synthetic-four-octet-v1',
                'choices'=>[['index'=>0,'message'=>['role'=>'assistant','content'=>$content],'finish_reason'=>'stop']],
                'usage'=>['prompt_tokens'=>$prompt,'prompt_cache_hit_tokens'=>0,'prompt_cache_miss_tokens'=>$prompt,'completion_tokens'=>$generated,'total_tokens'=>$prompt+$generated,'completion_tokens_details'=>['reasoning_tokens'=>0]]];
            if($this->mode==='missing-usage'){unset($chat['usage']);}
            if($this->mode==='token-contradiction'){$chat['usage']['prompt_tokens']++;$chat['usage']['prompt_cache_miss_tokens']++;$chat['usage']['total_tokens']++;}
            if($this->mode==='revoked-after-dispatch'){$this->fresh->d->f->revoke('policy',$this->fresh->d->policy['id']);}
            return new MockResponse(OnboardingAuthorityFixture::json($chat));
        }),fn():int=>$d->tick++);
        (new AugurMigration($d->f->store))->migrate($d->f->head());
    }
    private function configureAssignments(array &$p,OnboardingAuthorityFixture $f):void
    {
        $p['body']['application_mode']=$this->applicationMode;
        if($this->mode==='sole'){foreach($p['body']['targets'] as &$target){$target['permitted_bindings']=[$p['body']['candidate_bindings'][0]['binding_ref']];}unset($target);}
        $rule=json_decode(file_get_contents(dirname(__DIR__,4).'/docs/provider-onboarding/o0-assignment-selection-rule.json'),true,512,JSON_THROW_ON_ERROR)['runtime_body'];
        $rule=$f->store->make('imperium.bootstrap-assignment-selection-rule/v1','assignment-rule-v1',$rule);
        $f->sources[R::key(R::reference($rule))]=$rule;$rows=[];
        foreach($p['body']['targets'] as $target){foreach($p['body']['candidate_bindings'] as $binding){
            if(!in_array($binding['binding_ref'],$target['permitted_bindings'])){continue;}
            $tuple=['role'=>$target['role'],'provider'=>$binding['provider'],'model_id'=>$binding['model_id'],'model_version'=>$binding['model_version'],
                'binding_ref'=>$binding['binding_ref'],'configuration_ref'=>$binding['configuration_ref'],'profile_ref'=>$target['profile_ref'],'profile_generation'=>1,'binding_generation'=>1];
            $finding=$f->source('synthetic-assignment-profile-finding',['tuple'=>$tuple,'predicate'=>'profile.fits','disposition'=>'PASS']);
            $rows[]=['role'=>$target['role'],'binding_ref'=>$binding['binding_ref'],'profile_ref'=>$target['profile_ref'],'profile_generation'=>1,'binding_generation'=>1,
                'predicates'=>['profile.fits'=>['disposition'=>'PASS','evidence_refs'=>[$finding]]]];
            $tuples[$target['role']][]=$tuple;
        }}
        $profile=$f->source('assignment-profile-evidence',['rows'=>$rows]);$sets=[];
        foreach($tuples['courtyard.courtthane'] as $c){foreach($tuples['clavium.locksmith'] as $l){
            if($this->mode==='coupled' && $c['model_id']==='deepseek-v4-flash' && $l['model_id']==='deepseek-v4-flash'){continue;}
            $set=$f->source('assignment-set',['assignments'=>[$c,$l],'profile_evidence_ref'=>$profile]);
            $terms=$f->store->make('imperium.bootstrap-proposed-terms/v1','assign-terms-'.substr(R::hash($set),7,24),['effect'=>'APPLY_BOOTSTRAP_ASSIGNMENTS','terms'=>$set,'required_completed_refs'=>[]],[$set]);
            $f->sources[R::key(R::reference($terms))]=$terms;$sets[]=['set_ref'=>$set,'terms_ref'=>R::reference($terms)];
        }}
        $this->assignmentSets=$sets;$permission=$f->source('permitted-assignment-sets',['sets'=>$sets]);
        foreach($p['body']['effect_slots'] as &$slot){if($slot['effect']!=='APPLY_BOOTSTRAP_ASSIGNMENTS'){continue;}
            $slot['authority_mode']=$this->applicationMode==='B'?'signed_act':'policy_effect';
            $slot['terms_rule']=['kind'=>'assessed_assignment_set','permitted_tuple_set_ref'=>$permission,'result_group_ids'=>['W1','W2','W3'],
                'required_predicates_ref'=>$p['body']['requirements_ref'],'expected_assignments_ref'=>$p['body']['expected_assignments'],'selection_rule_ref'=>R::reference($rule)];
        }unset($slot);
        $this->assignmentPins=$f->sources;
    }
    public function evidence():\App\Imperium\Runtime\Onboarding\Assignment\AssignmentEvidence
    {
        return new class($this) implements \App\Imperium\Runtime\Onboarding\Assignment\AssignmentEvidence {
            public function __construct(private AssignmentFixture $f){}
            public function verify(array $policy,array $assignments,array $originals,array $responses):void
            {
                R::require($this->f->available,'SYNTHETIC_MODEL_UNAVAILABLE');
                foreach($originals as $key=>$record){R::require(isset($this->f->assignmentPins[$key]) && R::same($record,$this->f->assignmentPins[$key]),'SYNTHETIC_ASSIGNMENT_ORIGINAL');}
                foreach($assignments as $tuple){
                    R::require($tuple['profile_generation']===$this->f->profileGeneration && $tuple['binding_generation']===$this->f->bindingGeneration,'SYNTHETIC_GENERATION');
                    $found=0;foreach($originals as $h){if(($h['body']['kind']??null)!=='synthetic-assignment-profile-finding'){continue;}$b=Policy::content($h,'synthetic-assignment-profile-finding');if(R::same($b['tuple'],$tuple) && $b['predicate']==='profile.fits' && $b['disposition']==='PASS'){$found++;}}
                    R::require($found===1,'SYNTHETIC_PROFILE_PREDICATE');
                }
                R::require(count($responses)===3,'SYNTHETIC_ASSIGNMENT_RESPONSES');
            }
        };
    }
    public function ledger():\App\Imperium\Runtime\Onboarding\Ledger\CommandLedger
    {
        return new \App\Imperium\Runtime\Onboarding\Ledger\CommandLedger($this->fresh->d->f->store,$this->adapter,$this->adapter,$this->fresh->founding(),$this->evidence());
    }
    public function settings():\App\Imperium\Runtime\Onboarding\Assignment\PersistentSettings
    {return new \App\Imperium\Runtime\Onboarding\Assignment\PersistentSettings($this->fresh->d->f->store,$this->adapter,$this->evidence());}
    public function head():array
    {$head=$this->fresh->d->f->head();if($head['digest']!==null){$head['digest']='sha256:'.$head['digest'];}return $head;}
    public function replacementRequest(int $index=3):array
    {
        $d=$this->fresh->d;$store=$d->f->store;$snapshot=$this->settings()->snapshot();
        $a=\App\Imperium\Runtime\Onboarding\Assignment\ApplicationHistory::latest($store->state($store->journal->read()['state']));
        $scope=$d->f->source('assignment-change',['expected_application_ref'=>$snapshot['application_ref'],'prior_assignments'=>$snapshot['assignments'],
            'next_set_ref'=>$this->assignmentSets[$index]['set_ref'],'assessment_view_ref'=>$a['receipt']['body']['result_ref']]);
        $terms=$store->make('imperium.bootstrap-proposed-terms/v1','change-terms-'.bin2hex(random_bytes(8)),['effect'=>'APPLY_BOOTSTRAP_ASSIGNMENTS','terms'=>$scope,'required_completed_refs'=>[]],[$scope]);
        $admission=(new \App\Imperium\Runtime\Onboarding\AuthorityAdmission\Admission($store))->retain($d->f::json($d->f->sign($terms,'APPLY_BOOTSTRAP_ASSIGNMENTS',R::reference($d->policy))),$d->f::json($terms),[$d->f::json($d->f->sources[R::key($scope)])]);
        $q=$d->request(null,'change-command-'.bin2hex(random_bytes(8)));$q['schema']='imperium.assignment-change/v1';$q['predecessor_ref']=$a['command_ref'];
        $q['authority']=$d->f::authority($admission);$q['terms_ref']=R::reference($terms);return $q;
    }
    public function ready():void{$d=$this->fresh->d;$this->fresh->ready();$d->advance('select-base');$d->advance('map-base');$d->advance('found-augur');}
    public function assessed():void
    {
        $this->ready();foreach(['w1.attempt.0','w2.attempt.0','w3.attempt.0'] as $step){$this->fresh->d->advance($step);}
        (new \App\Imperium\Runtime\Onboarding\Assignment\AssignmentMigration($this->fresh->d->f->store))->migrate($this->fresh->d->f->head());
    }
    public function close():void{$this->fresh->close();}
}
