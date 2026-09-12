<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;
use App\Imperium\Runtime\Onboarding\Augur\{AugurAdapter,AugurMigration,CognitionEvidence,CognitionResources};
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules as R,Policy};
use App\Imperium\Runtime\Onboarding\DeepSeek\{Runtime,TokenEvidence,Tariff};
use App\Imperium\Runtime\Onboarding\ResponseValidation\{ParsedResponse,CandidateClaim};
use Symfony\Component\HttpClient\{MockHttpClient,Response\MockResponse};

/** An explicitly synthetic provider protocol, never a production tokenizer or entitlement. */
final class AugurReviewerMappingFixture
{
    public AugurFreshFixture $fresh;
    public AugurAdapter $adapter;
    public array $requests=[];
    public array $wires=[];
    public function __construct(public string $mode='valid')
    {
        $pins=[];
        $this->fresh=new AugurFreshFixture(configure:function(array &$p,OnboardingAuthorityFixture $f,array $profile)use(&$pins):void{
            // Reviewer mutation: genuinely admit an exact map whose selected model supports no output tokens.
            // All original production code and factual evidence verifiers remain unchanged.
            $oldMap=$p['body']['candidate_bindings'][0]['mapping_ref'];
            $map=Policy::content($f->sources[R::key($oldMap)],'runtime-binding-map');
            foreach($map['mappings'] as &$row){$row['supported_limits']['output_tokens']=0;}unset($row);
            $newMap=$f->source('runtime-binding-map',$map);
            foreach($p['body']['candidate_bindings'] as &$candidate){$candidate['mapping_ref']=$newMap;}unset($candidate);
            foreach($p['body']['effect_slots'] as &$slot){if($slot['slot_id']!=='slot.map-base'){continue;}
                $old=$slot['terms_rule']['object_ref'];$terms=$f->sources[R::key($old)];unset($f->sources[R::key($old)],$terms['record_digest']);
                $terms['body']['terms']=$newMap;$terms['sources']=[$newMap];$terms=R::seal($terms);
                $ref=R::reference($terms);$f->sources[R::key($ref)]=$terms;$slot['terms_rule']['object_ref']=$ref;
            }unset($slot);
            foreach($p['body']['steps'] as &$step){if($step['step_id']==='map-base'){$step['input_refs']=[['kind'=>'public_ref','ref'=>$newMap]];}}unset($step);
            $constitution=null;foreach($f->sources as $h){if(($h['body']['kind']??null)==='augur-constitution'){$constitution=R::reference($h);}}
            foreach($p['body']['targets'] as &$target){$target['profile_ref']=$f->source('synthetic-role-profile',['role'=>$target['role'],'predicate_ids'=>$target['predicate_ids']]);}unset($target);
            $account=$f->source('synthetic-cognition-account',['credential_ref'=>$p['body']['credential_ref'],'account_scope'=>'synthetic-account','methods'=>['POST'],'models'=>['deepseek-v4-flash','deepseek-v4-pro'],'not_before'=>$f->now-1,'expires_at'=>$f->now+1800]);
            $token=$f->source('synthetic-cognition-tokenizer',['algorithm'=>'fixed-four-octet-blocks/v1','frame_tokens'=>8,'context_tokens'=>32768,'generated_tokens'=>4096]);
            $tariff=$f->source('synthetic-cognition-tariff',['account_scope'=>'synthetic-account','model'=>'deepseek-v4-flash','miss_rate'=>440000,'hit_rate'=>44000,'output_rate'=>1320000,'rounding'=>'ceil_each_meter_microusd','fees'=>0,'not_before'=>$f->now-1,'expires_at'=>$f->now+1800]);
            $predicates=array_fill_keys(CandidateClaim::PREDICATES,'PASS');if($this->mode==='no-fit'){$predicates['exact_role_profile_fit']='FAIL';}
            $findings=$f->source('synthetic-cognition-findings',['models'=>array_column($p['body']['candidate_bindings'],'binding_ref'),
                'profiles'=>[$profile,...array_column($p['body']['targets'],'profile_ref')],'capacity_tiers'=>$this->mode==='no-fit'?[]:[array_column($p['body']['candidate_bindings'],'binding_ref')],
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
        },constitutionLifetime:$this->mode==='expired-holder'?200:1800);
        $d=$this->fresh->d;
        $verifier=new class($pins,$d->policy['body']['credential_ref']) implements CognitionEvidence {
            public function __construct(private array $pins,private array $credential){}
            private function original(array $originals,array $ref,string $kind):array{R::require(isset($originals[R::key($ref)],$this->pins[R::key($ref)]) && R::same($originals[R::key($ref)],$this->pins[R::key($ref)]),'SYNTHETIC_COGNITION_ORIGINAL');return Policy::content($originals[R::key($ref)],$kind);}
            public function resources(array $c,array $originals,string $wire,string $model,int $now):CognitionResources{
                $a=$this->original($originals,$c['account_ref'],'synthetic-cognition-account');$t=$this->original($originals,$c['token_ref'],'synthetic-cognition-tokenizer');$rates=$this->original($originals,$c['tariff_ref'],'synthetic-cognition-tariff');
                R::require(R::same($a['credential_ref'],$this->credential) && $a['account_scope']===$c['account_scope'] && $a['methods']===['POST'] && in_array($model,$a['models'],true) && $a['not_before']<=$now && $c['expires_at']<=$a['expires_at'],'SYNTHETIC_INVOKE_SCOPE');
                R::require($t['algorithm']==='fixed-four-octet-blocks/v1' && $t['frame_tokens']===8,'SYNTHETIC_TOKENIZER');
                // Exact tokenizer of this fictional mock protocol. No claim about DeepSeek tokenization.
                $tokens=count(str_split($wire,4))+$t['frame_tokens'];
                return new CognitionResources(new TokenEvidence('sha256:'.hash('sha256',$wire),$t['algorithm'],'synthetic-eight-token-frame/v1',$tokens,$t['generated_tokens'],$t['context_tokens']),
                    new Tariff($rates['account_scope'],$rates['model'],$rates['not_before'],$rates['expires_at'],$rates['miss_rate'],$rates['hit_rate'],$rates['output_rate'],$rates['rounding'],$rates['fees']));
            }
            public function response(ParsedResponse $response,array $c,array $originals):void{
                $f=$this->original($originals,$c['evidence_refs'][0],'synthetic-cognition-findings');
                R::require(count(array_filter($f['profiles'],static fn(array $profile):bool=>R::same($profile,$c['profile_ref'])))===1,'SYNTHETIC_RESULT_PROFILE');
                foreach($response->candidateRows as $row){$ref=['schema'=>$row->bindingRef->schema,'id'=>$row->bindingRef->id,'digest'=>$row->bindingRef->digest];
                    R::require(count(array_filter($f['models'],static fn(array $model):bool=>R::same($model,$ref)))===1 && $row->fit===$f['fit'],'SYNTHETIC_RESULT_FIT');
                    foreach($row->predicates as $name=>$claim){R::require($claim->disposition===$f['predicates'][$name] && count($claim->evidenceRefs)===1 && $claim->evidenceRefs[0]->digest===$c['evidence_refs'][0]['digest'],'SYNTHETIC_RESULT_PREDICATE');}
                }
                R::require($response->contradictions===[] && $response->unknowns===[],'SYNTHETIC_RESULT_CONTRADICTION');
                if($response->groupId!=='W1'){
                    if($f['capacity_tiers']===[]){R::require($response->capacityOrder->kind==='unknown','SYNTHETIC_CAPACITY_EVIDENCE');}
                    else{$tiers=[];foreach($response->capacityOrder->tiers as $tier){$refs=[];foreach($tier['binding_refs'] as $ref){$refs[]=['schema'=>$ref->schema,'id'=>$ref->id,'digest'=>$ref->digest];}$tiers[]=R::refs($refs);}
                        R::require($response->capacityOrder->kind==='ranked' && R::same($tiers,array_map(R::refs(...),$f['capacity_tiers'])),'SYNTHETIC_CAPACITY_EVIDENCE');}
                }
            }
        };
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
            if($input['group_id']!=='W1'){$body['capacity_order']=$this->mode==='no-fit'?['kind'=>'unknown','reason'=>'No verified fit','evidence_refs'=>$evidence]:['kind'=>'ranked','tiers'=>[['binding_refs'=>array_column($input['candidates'],'binding_ref'),'rationale'=>'Synthetic equal capacity','evidence_refs'=>$evidence]]];}
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
    public function ready():void{$d=$this->fresh->d;$this->fresh->ready();$d->advance('select-base');$d->advance('map-base');$d->advance('found-augur');}
    public function close():void{$this->fresh->close();}
}
