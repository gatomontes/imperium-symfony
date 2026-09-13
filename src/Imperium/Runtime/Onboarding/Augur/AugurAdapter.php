<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Augur;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore,Rules as R,Policy};
use App\Imperium\Runtime\Onboarding\Ledger\{ContextualPreparedOperation,SourceAuthority,LedgerState,AssessmentGroups};
use App\Imperium\Runtime\Onboarding\DeepSeek\{AccessAdapter,KeySource,Wire,ProviderResponse};
use App\Imperium\Runtime\Onboarding\ResponseValidation\{ExpectedContext,ParsedResponse};

/** Fixed infrastructure composition. Public methods produce facts, never delivery capabilities. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class AugurAdapter implements ContextualPreparedOperation,SourceAuthority
{
    public const SYSTEM='Assess only the frozen public evidence. Return the requested Imperium assessment JSON schema and exact context. Claims are not authority.';
    public function __construct(private AccessAdapter $access,public FreshProducer $founding,private KeySource $keys,private CognitionEvidence $evidence=new MissingCognitionEvidence())
    {
        $access->requireDeliverySource($keys);
    }
    public function requireDeliverySource(?KeySource $source):void{$this->access->requireDeliverySource($source);R::require($source===$this->keys,'DEEPSEEK_DELIVERY_SOURCE_MISMATCH');}
    public function prepare(array $terms):array{return $this->access->prepare($terms);}
    public function prepareCurrent(AuthorityStore $store,array $state,array $policy,array $step,array $terms):array
    {
        if($terms['body']['effect']==='AUTHORIZE_BOOTSTRAP_ACCESS'){return $this->access->prepare($terms);}
        return $this->context($store,$state,$policy,$step,$terms)['operation'];
    }
    private function step(array $policy,array $terms):array
    {
        $matches=[];foreach($policy['body']['effect_slots'] as $slot){if($slot['terms_rule']['kind']==='exact' && R::same($slot['terms_rule']['object_ref'],R::reference($terms))){
            foreach($policy['body']['steps'] as $step){if($step['effect_slot_id']===$slot['slot_id']){$matches[]=$step;}}
        }}R::require(count($matches)===1,'COMMISSION_STEP');return $matches[0];
    }
    private function context(AuthorityStore $store,array $state,array $policy,array $step,array $terms):array
    {
        return \App\Imperium\Runtime\Onboarding\AuthorityAdmission\StrictJson::within(fn():array=>$this->resolveContext($store,$state,$policy,$step,$terms));
    }
    private function resolveContext(AuthorityStore $store,array $state,array $policy,array $step,array $terms):array
    {
        $s=$store->state($state);R::require(in_array($s['schema'],['imperium.onboarding-authority-state/v3','imperium.onboarding-authority-state/v4'],true) && $terms['body']['effect']==='AUTHORIZE_BOOTSTRAP_ASSESSMENT'
            && R::same($this->step($policy,$terms),$step),'COMMISSION_STEP');
        $originals=[];$load=function(array $ref)use($store,$s,&$originals):array{$key=R::key($ref);if(isset($originals[$key])){return $originals[$key];}$h=$store->checkSource($s,$ref);$originals[$key]=$h;return $h;};
        $load(R::reference($policy));$load(R::reference($terms));$source=$load($terms['body']['terms']);$c=Policy::content($source,'augur-assessment-commission');
        R::object($c,['schema','group_id','founding_step_id','constitution_ref','profile_ref','account_scope','account_ref','token_ref','tariff_ref','evidence_refs','not_before','expires_at']);
        R::require($c['schema']==='imperium.augur-assessment-commission/v1' && $c['founding_step_id']==='found-augur'
            && $c['group_id']===$step['run_condition']['group_id'] && R::time($c['not_before'])<=$store->now() && $store->now()<R::time($c['expires_at']),'COMMISSION_SCOPE');
        $complete=LedgerState::step($s,$policy,'found-augur');$holder=$this->founding->current($store,$s,$complete['completion']['body']['result_refs'][0]);$b=$holder['body'];
        R::require(R::same($b['policy_ref'],R::reference($policy)) && R::same($b['constitution_ref'],$c['constitution_ref']),'COMMISSION_HOLDER');
        $profile=match($c['group_id']){'W1'=>$b['profile_ref'],'W2'=>$policy['body']['targets'][0]['profile_ref'],'W3'=>$policy['body']['targets'][1]['profile_ref'],default=>throw new \RuntimeException('O2_COMMISSION_GROUP')};
        R::require(R::same($c['profile_ref'],$profile),'COMMISSION_PROFILE');
        foreach(['constitution_ref','profile_ref','account_ref','token_ref','tariff_ref'] as $field){$load(R::ref($c[$field]));}
        $binding=Policy::content($load($policy['body']['credential_ref']),'credential-binding');
        R::require(R::same($binding,['authentication'=>'api-key','provider'=>'deepseek','opaque_binding'=>$this->keys->generation()]),'DEEPSEEK_KEY_CURRENT');
        R::require(R::same($b['binding']['adapter_ref'],$policy['body']['adapter_ref']) && R::same(Policy::content($load($policy['body']['adapter_ref']),'adapter-description'),['schema'=>'imperium.deepseek-adapter/v1','adapter'=>Wire::ADAPTER]),'COMMISSION_ADAPTER');
        $config=Policy::content($load($b['binding']['configuration_ref']),'request-configuration');
        $supported=MappingLimits::resolve(Policy::content($load($b['binding']['mapping_ref']),'runtime-binding-map'),
            $b['binding'],$config,$load,$store->now(),$policy['body']['expires_at']);
        $evidence=R::refs($c['evidence_refs']);R::require($evidence!==[] && count($evidence)<=256 && R::same($evidence,$c['evidence_refs']),'COMMISSION_EVIDENCE');
        $public=[];foreach($evidence as $ref){$public[]=$load($ref);}
        $inputs=[];foreach($step['input_refs'] as $selector){
            if($selector['kind']==='public_ref'){$inputs[]=$load($selector['ref']);}
            else{
                $outcome=AssessmentGroups::success($s,$policy,$selector['group_id']);$matches=[];
                foreach($s['claims'] as $claim){if(R::same(R::reference($claim['record']),$outcome['claim_ref'])){$matches[]=$claim;}}
                R::require(count($matches)===1 && $matches[0]['settled']!==null && count($matches[0]['custody'])===5,'COMMISSION_PREDECESSOR');
                $inputs[]=['outcome'=>$outcome,'envelope'=>$matches[0]['custody'][4]['body']['response_envelope']];
            }
        }
        $payload=['schema'=>'imperium.augur-cognition-input/v1','group_id'=>$c['group_id'],'holder'=>['ref'=>R::reference($holder),'generation'=>$b['generation'],'binding'=>$b['binding']],'profile'=>$load($profile),
            'workload'=>$load($policy['body']['workload_ref']),'candidates'=>$policy['body']['candidate_bindings'],'inputs'=>$inputs,'evidence'=>$public];
        $digest=R::hash($payload);$expected=ExpectedContext::fromInputs($c['group_id'],$digest,R::reference($holder),$profile,array_column($policy['body']['candidate_bindings'],'binding_ref'),$evidence);
        $wire=Wire::cognition($b['binding']['model_id'],$config,self::SYSTEM,CanonicalJson::encode(['input_digest'=>$digest,'input'=>$payload]));
        [, $slot, $authority]=(new \App\Imperium\Runtime\Onboarding\Ledger\CommandLedger($store))->authority($s,$policy,$step);
        $expiry=min($c['expires_at'],$b['expires_at'],$policy['body']['expires_at'],$slot['expires_at'],$authority['admission']['envelope']['payload']['expires_at'],$s['trust']['body']['expires_at']);
        $resources=$this->evidence->resources($c,$originals,$wire,$b['binding']['model_id'],$store->now());
        R::require($resources->tariff->account===$c['account_scope'],'COMMISSION_ACCOUNT');Wire::preflight($wire,$b['binding']['model_id'],$resources->tokens,$resources->tariff,$store->now(),$expiry);
        $op=['schema'=>'imperium.bootstrap-prepared-operation/v1','wire'=>$wire,'destination'=>'https://api.deepseek.com:443/chat/completions','method'=>'POST','provider'=>'deepseek',
            'model'=>$b['binding']['model_id'],'configuration_ref'=>$b['binding']['configuration_ref'],'credential_operation'=>'deepseek.augur.'.$c['group_id'],
            'adapter'=>Wire::ADAPTER,'maximum'=>MappingLimits::MAXIMUM,
            'expires_at'=>$expiry,'authority_source'=>['kind'=>'assessment','commission_ref'=>R::reference($source),'holder_ref'=>R::reference($holder)]];
        R::require(MappingLimits::supports($supported,$op['maximum']),'MAPPING_OPERATION_LIMIT');
        return ['operation'=>$op,'resources'=>$resources,'expected'=>$expected,'commission'=>$c,'originals'=>$originals];
    }
    public function verify(AuthorityStore $store,array $state,array $policy,string $effect,array $terms,array $operation):void
    {
        if($effect==='AUTHORIZE_BOOTSTRAP_ACCESS'){$this->access->verify($store,$state,$policy,$effect,$terms,$operation);return;}
        R::require($effect==='AUTHORIZE_BOOTSTRAP_ASSESSMENT' && R::same($this->prepareCurrent($store,$state,$policy,$this->step($policy,$terms),$terms),$operation),'COMMISSION_OPERATION');
    }
    public function dispatchResources(AuthorityStore $store,array $state,array $operation):CognitionResources
    {
        $s=$store->state($state);$matches=[];foreach($s['claims'] as $claim){if(R::same($claim['operation']['prepared'],$operation)){$matches[]=$claim;}}
        R::require(count($matches)===1,'COMMISSION_CLAIM');$claim=$matches[0];$command=LedgerState::command($s,$claim['record']['body']['command_ref']);$policy=$store->checkSource($s,$claim['record']['body']['policy_ref']);
        $step=array_values(array_filter($policy['body']['steps'],static fn(array $v):bool=>$v['step_id']===$command['result']['step_id']))[0];
        [,,$facts]=(new \App\Imperium\Runtime\Onboarding\Ledger\CommandLedger($store))->authority($s,$policy,$step);
        $context=$this->context($store,$state,$policy,$step,$facts['terms']);R::require(R::same($context['operation'],$operation),'COMMISSION_OPERATION');return $context['resources'];
    }
    public function validateResponse(AuthorityStore $store,array $state,array $policy,string $effect,array $operation,array $envelope):string
    {
        if($effect==='AUTHORIZE_BOOTSTRAP_ACCESS'){return $this->access->validateResponse($store,$state,$policy,$effect,$operation,$envelope);}
        [$context,$mapped]=$this->responseContext($store,$state,$policy,$operation,$envelope);
        if($mapped['finish_reason']!=='stop'){return 'TERMINAL_FAILURE';}
        try{$this->parseAssessment($context,$mapped);}
        catch(\InvalidArgumentException|\RuntimeException){return 'TERMINAL_FAILURE';}return 'SUCCEEDED';
    }
    /** Internal locked-owner port. Rechecks current evidence; its return is not authority. */
    public function verifiedAssessment(AuthorityStore $store,array $state,array $policy,array $operation,array $envelope):ParsedResponse
    {
        [$context,$mapped]=$this->responseContext($store,$state,$policy,$operation,$envelope);
        R::require($mapped['finish_reason']==='stop','ASSESSMENT_INCOMPLETE');
        return $this->parseAssessment($context,$mapped);
    }
    private function parseAssessment(array $context,array $mapped):ParsedResponse
    {
        $parsed=ParsedResponse::parse($mapped['content'],$context['expected']);
        $this->evidence->response($parsed,$context['commission'],$context['originals']);
        return $parsed;
    }
    private function responseContext(AuthorityStore $store,array $state,array $policy,array $operation,array $envelope):array
    {
        $s=$store->state($state);$terms=[];foreach($policy['body']['effect_slots'] as $slot){if($slot['effect']==='AUTHORIZE_BOOTSTRAP_ASSESSMENT' && $slot['terms_rule']['kind']==='exact'){
            $h=$store->checkSource($s,$slot['terms_rule']['object_ref']);if(R::same($h['body']['terms'],$operation['authority_source']['commission_ref'])){$terms[]=$h;}
        }}R::require(count($terms)===1,'COMMISSION_RESPONSE');$context=$this->context($store,$state,$policy,$this->step($policy,$terms[0]),$terms[0]);
        R::require(R::same($context['operation'],$operation),'COMMISSION_OPERATION');
        $mapped=ProviderResponse::cognition($envelope['response'],$operation['model'],$context['resources']->tariff,$envelope['metadata']['usage']['milliseconds']);
        R::require($mapped['usage']['input_tokens']<=$context['resources']->tokens->inputMaximum && $mapped['usage']['output_tokens']<=$context['resources']->tokens->generatedMaximum,'RESPONSE_EXCEEDS_TOKEN_EVIDENCE');
        R::require($mapped['identity']===$envelope['metadata']['provider_response_id'] && R::same($mapped['usage'],$envelope['metadata']['usage'])
            && $envelope['operation_digest']===R::hash($operation) && $envelope['metadata']['provenance']===Wire::ADAPTER,'DEEPSEEK_RESPONSE_ATTRIBUTION');
        return [$context,$mapped];
    }
    public function select(AuthorityStore $store,array $state,array $policy,array $step):array{return $this->access->select($store,$state,$policy,$step);}
}
