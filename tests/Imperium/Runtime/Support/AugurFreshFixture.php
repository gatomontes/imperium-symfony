<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;

use App\Imperium\Runtime\Onboarding\Augur\{BaseProjection,BaseEvidence};
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules as R,Policy};
use App\Imperium\Runtime\Onboarding\DeepSeek\{AccessAdapter,EvidenceVerifier,Runtime};
use App\Imperium\Runtime\Onboarding\ResponseValidation\CandidateClaim;
use Symfony\Component\HttpClient\{MockHttpClient,Response\MockResponse};

/** Owns only temporary synthetic source material. Real B0 signing/admission and O2/O3 execution are reused. */
final class AugurFreshFixture
{
    public DeepSeekFixture $d;
    public BaseProjection $base;
    public array $manifest;
    public array $observations=[];
    public array $constitution;
    public array $constitutionalOriginals;
    public ?\Closure $baseHook=null;
    public function __construct(string $case='valid',?\Closure $configure=null,int $constitutionLifetime=1800)
    {
        $pinned=[];
        $this->d=new DeepSeekFixture(change:function(array &$policy,OnboardingAuthorityFixture $f)use($case,&$pinned,$configure,$constitutionLifetime):void {
            $snapshot=$f->source('synthetic-model-catalogue',['object'=>'list','data'=>array_map(static fn(array $c):array=>['id'=>$c['model_id'],'object'=>'model','created'=>1,'owned_by'=>'deepseek'],$policy['body']['candidate_bindings'])]);
            $map=['snapshot_ref'=>$snapshot,'provider'=>'deepseek','mappings'=>[], 'adapter_ref'=>$policy['body']['adapter_ref'],'expires_at'=>$policy['body']['expires_at']];
            foreach($policy['body']['candidate_bindings'] as $c){$map['mappings'][]=['model_ref'=>$c['binding_ref'],'dispatch_id'=>$c['model_id'],
                'configuration_schema_digest'=>R::hash(\App\Imperium\Runtime\Onboarding\DeepSeek\Wire::CONFIGURATION),
                'supported_limits'=>['calls'=>1,'input_tokens'=>16384,'output_tokens'=>4096,'cost_microusd'=>100000,'milliseconds'=>60000],
                'revision_pin_limitation'=>$c['revision_pin']];}
            if($case==='wrong-map'){$map['mappings'][0]['dispatch_id']='deepseek-v4-pro';}
            $mapRef=$f->source('runtime-binding-map',$map);
            foreach($policy['body']['candidate_bindings'] as &$c){$c['mapping_ref']=$mapRef;}unset($c);
            foreach($policy['body']['effect_slots'] as &$slot){if($slot['slot_id']==='slot.map-base'){
                $old=$slot['terms_rule']['object_ref'];$term=$f->sources[R::key($old)];unset($f->sources[R::key($old)],$term['record_digest']);
                $term['body']['terms']=$mapRef;$term['sources']=[$mapRef];$term=R::seal($term);
                $f->sources[R::key(R::reference($term))]=$term;$slot['terms_rule']['object_ref']=R::reference($term);
            }}unset($slot);
            $profile=$f->source('augur-base-profile',['seat'=>'oracle.augur','predicate_ids'=>['profile.fits']]);
            $manifest=['schema'=>'imperium.augur-base-input/v1',
                'approval_ref'=>$f->source('synthetic-policy-approval',['scope'=>'public-comparison']),
                'decision_ref'=>$f->source('synthetic-route-decision',['route'=>'FRESH']),
                'selection_rule_ref'=>$f->source('base-rule',['algorithm'=>'least_cost_initial_augur_fixed_workload_v1']),
                'profile_ref'=>$profile,'universe_ref'=>$f->source('finite-universe',array_column($policy['body']['candidate_bindings'],'binding_ref')),
                'account_scope'=>'synthetic-account','rows'=>[],'evidence_refs'=>[]];
            foreach($policy['body']['candidate_bindings'] as $index=>$binding){
                $evidence=[];
                foreach(['catalogue','capability','tariff','access'] as $category){
                    $raw=$f->source('synthetic-provider-bytes',['schema'=>'imperium.synthetic-provider-contract/v1',
                        'model'=>$binding['model_id'],'category'=>$category,'account_scope'=>'synthetic-account','configuration_ref'=>$binding['configuration_ref'],
                        'context_tokens'=>32768,'max_generated_tokens'=>4096,'input_ceiling_with_framing'=>16384,
                        'generated_accounting'=>'TOTAL_INCLUDES_REASONING','methods'=>['POST'],'data_scope'=>'PUBLIC_ONLY',
                        'currency'=>'USD','rounding'=>'FIXED_RATIONAL_CEIL','fixed_fees_micro_usd'=>0,
                        'input_per_million_micro_usd'=>$index===0?440000:1320000,'generated_per_million_micro_usd'=>$index===0?1320000:3960000]);
                    $pinned[R::key($raw)]=$f->sources[R::key($raw)];
                    $source=$f->source('synthetic-provider-source',['public_bytes_ref'=>$raw,'provider'=>'deepseek']);
                    $o=['source_ref'=>$source,'content_ref'=>$raw,'source_locator'=>'synthetic://provider/'.$binding['model_id'].'/'.$category,
                        'category'=>$category,'officialness'=>'OFFICIAL','observed_at'=>$f->now*1000,'effective_from'=>($f->now-1)*1000,'effective_until'=>($f->now+1800)*1000,
                        'scope'=>['provider'=>'deepseek','model_id'=>$binding['model_id'],'model_version'=>$binding['model_version'],
                            'configuration_digest'=>$binding['configuration_ref']['digest'],'profile_ref'=>$profile,'account_scope'=>'synthetic-account','data_scope'=>'PUBLIC_ONLY'],
                        'claim_keys'=>[...CandidateClaim::PREDICATES,'profile.fits'],'disposition'=>'PASS','reason'=>'Explicit synthetic provider fixture'];
                    if($case==='stale'){$o['observed_at']=($f->now-800000)*1000;}
                    $evidence[$category]=$f->source('augur-base-observation',$o);$this->observations[]=$evidence[$category];$manifest['evidence_refs'][]=$evidence[$category];
                }
                $facts=['binding_ref'=>$binding['binding_ref'],'predicates'=>[],
                    'bounds'=>['context_tokens'=>32768,'max_generated_tokens'=>4096,'tokenizer_framing'=>'PASS','generated_accounting'=>'TOTAL_INCLUDES_REASONING',
                        'invocation_bounds'=>'PASS','adapter_support'=>'PASS','access'=>'INVOKE','data_scope'=>'PUBLIC_ONLY'],'tariffs'=>[],'contradictions'=>[]];
                foreach([...CandidateClaim::PREDICATES,'profile.fits'] as $claim){
                    $category=match($claim){'identity_runtime_mapping','evidence_support'=>'catalogue','access','admissibility_data_constraints'=>'access','complete_tariff'=>'tariff',default=>'capability'};
                    $facts['predicates'][$claim]=['disposition'=>'PASS','evidence_refs'=>[$evidence[$category]],'reason'=>'Explicit synthetic finding'];
                }
                foreach(['W1','W2','W3'] as $group){$facts['tariffs'][]=['group_id'=>$group,'currency'=>'USD','billing_model'=>'FIXED_RATIONAL_CEIL','fee_status'=>'COMPLETE','fixed_fees_micro_usd'=>0,
                    'fee_evidence_refs'=>[$evidence['tariff']],'meters'=>[
                        ['kind'=>'uncached_input','quantity'=>16384,'numerator'=>$index===0?440000:1320000,'denominator'=>1000000,'units'=>'tokens','evidence_refs'=>[$evidence['tariff']]],
                        ['kind'=>'generated_total','quantity'=>4096,'numerator'=>$index===0?1320000:3960000,'denominator'=>1000000,'units'=>'tokens','evidence_refs'=>[$evidence['tariff']]]]];}
                if($case==='no-fit'){$facts['predicates']['profile.fits']['disposition']='UNKNOWN';}
                $factsRef=$f->source('augur-base-facts',$facts);
                // Pin the synthetic provider facts before any adversarial signed substitution.
                $pinned[R::key($factsRef)]=$f->sources[R::key($factsRef)];
                if($case==='false-tokens'){$facts['bounds']['context_tokens']=1000000;$factsRef=$f->source('augur-base-facts',$facts);}
                if($case==='false-tariff'){$facts['tariffs'][0]['meters'][0]['numerator']=0;$factsRef=$f->source('augur-base-facts',$facts);}
                if($case==='false-account'){$facts['bounds']['access']='KEY_PRESENT';$factsRef=$f->source('augur-base-facts',$facts);}
                $manifest['rows'][]=['binding_ref'=>$binding['binding_ref'],'facts_ref'=>$factsRef];
            }
            foreach($manifest['evidence_refs'] as $ref){$pinned[R::key($ref)]=$f->sources[R::key($ref)];}
            if($case==='profile'){$manifest['profile_ref']=$f->source('augur-base-profile',['seat'=>'courtyard.courtthane','predicate_ids'=>['profile.fits']]);}
            if($case==='version'){$manifest['schema']='imperium.augur-base-input/v99';}
            $this->manifest=$f->source('augur-base-input',$manifest);
            $grant=['schema'=>'imperium.augur-constitution/v1','instance_id'=>$f->store->instance,'operator_id'=>$f->store->operator,
                'root_identity'=>(new \App\Imperium\Runtime\Bootstrap\OperatorRootOwnership($f->root))->identity(),'seat'=>'oracle.augur',
                'charter_ref'=>$f->source('synthetic-augur-charter',['seat'=>'oracle.augur','route'=>'FRESH']),
                'persona_ref'=>$f->source('synthetic-augur-persona',['seat'=>'oracle.augur','version'=>'synthetic-v1']),
                'profile_ref'=>$profile,'not_before'=>$f->now-1,'expires_at'=>$f->now+$constitutionLifetime];
            $constitution=$f->source('augur-constitution',$grant);$this->constitution=$f->sources[R::key($constitution)];
            $this->constitutionalOriginals=[];foreach(['charter_ref','persona_ref','profile_ref'] as $field){$ref=$grant[$field];$this->constitutionalOriginals[R::key($ref)]=$f->sources[R::key($ref)];}
            $permitted=[];
            foreach(array_slice($policy['body']['candidate_bindings'],0,1) as $i=>$binding){
                $intent=$f->source('augur-founding-intent',['constitution_ref'=>$constitution,'binding_ref'=>$binding['binding_ref']]);
                $terms=$f->store->make('imperium.bootstrap-proposed-terms/v1','fresh-terms-'.$i,['effect'=>'CONSTITUTE_FOUNDING_AUGUR','terms'=>$intent,'required_completed_refs'=>[]],[$intent]);
                $ref=R::reference($terms);$f->sources[R::key($ref)]=$terms;$permitted[]=$ref;
            }
            foreach($policy['body']['effect_slots'] as &$slot){if($slot['slot_id']==='slot.found-augur'){$slot['terms_rule']['permitted_object_refs']=R::refs($permitted);}}unset($slot);

            foreach($policy['body']['steps'] as &$step){
                if($step['step_id']==='select-base'){$step['input_refs']=[['kind'=>'public_ref','ref'=>$policy['body']['requirements_ref']],['kind'=>'public_ref','ref'=>$this->manifest]];}
                elseif($step['step_id']==='map-base'){$step['input_refs']=[['kind'=>'public_ref','ref'=>$mapRef]];}
            }unset($step);
            if($configure!==null){$configure($policy,$f,$profile);}
        });
        $verifier=new SyntheticAugurBaseEvidence($pinned,function():void{if($this->baseHook!==null){($this->baseHook)();}});
        $this->base=$case==='missing-evidence'?new BaseProjection():new BaseProjection($verifier);
        $d=$this->d;
        $accountRef=Policy::content($d->grant,'deepseek-access-grant')['account_evidence_ref'];
        $account=$d->f->sources[R::key($accountRef)];
        $access=new class($account) implements EvidenceVerifier {
            public function __construct(private array $pin) {}
            public function access(array $original,array $grant,int $now):void {
                R::require(R::same($original,$this->pin),'SYNTHETIC_ACCOUNT_ORIGINAL');
                $v=Policy::content($original,'deepseek-account-observation');
                R::require($v['schema']==='imperium.synthetic-deepseek-account/v1' && $v['not_before']<=$now && $now<$v['expires_at']
                    && $v['method']==='GET' && $v['cost_microusd']===0 && R::same($v['credential_binding_ref'],$grant['executor']['credential_binding_ref']),'SYNTHETIC_ACCOUNT_SCOPE');
            }
        };
        $d->adapter=new AccessAdapter($d->grant,$access,$d->keys,$this->base);
        $d->runtime=new Runtime($d->f->store,$d->adapter,$d->keys,$d->envelopes,new MockHttpClient(function(string $method,string $url,array $options)use($d){
            $authorization=array_values(array_filter($options['headers'],static fn(string $h):bool=>str_starts_with(strtolower($h),'authorization:')));
            R::require(count($authorization)===1 && str_starts_with($authorization[0],'Authorization: Bearer synthetic-'),'SYNTHETIC_MOCK_AUTHENTICATION');
            $d->requests[]=['method'=>$method,'url'=>$url];
            return new MockResponse(DeepSeekFixture::listing());
        }),fn():int=>$d->tick++);
    }
    public function ready():void{$this->d->ready();$this->d->advance('access');}

    public function founding():\App\Imperium\Runtime\Onboarding\Augur\FreshProducer
    {
        $verifier=new class($this->constitution,$this->constitutionalOriginals) implements \App\Imperium\Runtime\Onboarding\Augur\ConstitutionEvidence {
            public function __construct(private array $constitution,private array $pins){}
            public function verify(array $constitution,array $originals):void {
                R::require(R::same($constitution,$this->constitution),'SYNTHETIC_CONSTITUTION_ORIGINAL');
                foreach($this->pins as $key=>$pin){R::require(isset($originals[$key]) && R::same($originals[$key],$pin),'SYNTHETIC_CONSTITUTION_ARTIFACT');}
                $grant=Policy::content($constitution,'augur-constitution');
                R::require(Policy::content($originals[R::key($grant['charter_ref'])],'synthetic-augur-charter')===['route'=>'FRESH','seat'=>'oracle.augur'],'SYNTHETIC_CONSTITUTION_CHARTER');
            }
        };
        return new \App\Imperium\Runtime\Onboarding\Augur\FreshProducer(new \App\Imperium\Runtime\Bootstrap\OperatorRootOwnership($this->d->f->root),$this->base,$verifier);
    }
    public function close():void{$this->d->close();}
}
