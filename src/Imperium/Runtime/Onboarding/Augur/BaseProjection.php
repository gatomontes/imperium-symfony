<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Augur;

use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore,Policy,Rules as R};
use App\Imperium\Runtime\Onboarding\BaseSelection\{BaseInput,BaseSelector,BaseProposal};
use App\Imperium\Runtime\Onboarding\DeepSeek\Wire;
use App\Imperium\Runtime\Onboarding\ResponseValidation\CandidateClaim;

/** Pure locked-state projection; neither an alternate admission route nor a holder producer. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class BaseProjection
{
    public function __construct(private BaseEvidence $evidence = new MissingBaseEvidence()) {}

    public function propose(AuthorityStore $store, array $state, array $policy, array $step,?int $evaluatedAt=null,?array &$snapshot=null): BaseProposal
    {
        $s=$store->state($state);R::require($evaluatedAt===null || ($policy['created_at']<=R::time($evaluatedAt) && $evaluatedAt<=$store->now()),'BASE_EVALUATION_TIME'); $originals=[];
        $load=function(array $ref)use($store,$s,&$originals):array {
            $key=R::key($ref);
            if (isset($originals[$key])) { return $originals[$key]; }
            $h=$store->checkSource($s,$ref);$originals[$key]=$h;return $h;
        };
        R::require(R::same($load(R::reference($policy)),$policy),'BASE_POLICY_ORIGINAL');
        $expected=array_values(array_filter($policy['body']['steps'],static fn(array $v):bool=>$v['step_id']==='select-base'));
        R::require(count($expected)===1 && R::same($expected[0],$step),'BASE_STEP_ORIGINAL');
        R::require($step['input_refs'][0]['kind']==='public_ref' && $step['input_refs'][1]['kind']==='public_ref'
            && R::same($step['input_refs'][0]['ref'],$policy['body']['requirements_ref']),'BASE_INPUT_SCOPE');
        $manifest=Policy::content($load($step['input_refs'][1]['ref']),'augur-base-input');
        R::object($manifest,['schema','approval_ref','decision_ref','selection_rule_ref','profile_ref','universe_ref','account_scope','rows','evidence_refs']);
        R::require($manifest['schema']==='imperium.augur-base-input/v1','BASE_PROJECTION_VERSION');
        $context=['policy_ref'=>R::reference($policy),'workload_ref'=>$policy['body']['workload_ref'],'requirements_ref'=>$policy['body']['requirements_ref']];
        foreach(['approval_ref','decision_ref','selection_rule_ref','profile_ref','universe_ref'] as $key){$context[$key]=R::reference($load(R::ref($manifest[$key])));}
        R::require(R::same(Policy::content($load($context['selection_rule_ref']),'base-rule'),['algorithm'=>BaseProposal::ALGORITHM]),'BASE_RULE');
        R::require(R::same(R::refs(Policy::content($load($context['universe_ref']),'finite-universe')),R::refs(array_column($policy['body']['candidate_bindings'],'binding_ref'))),'BASE_UNIVERSE');
        $requirements=Policy::content($load($context['requirements_ref']),'requirements');
        $load($context['workload_ref']);
        $profile=Policy::content($load($context['profile_ref']),'augur-base-profile');R::object($profile,['seat','predicate_ids']);
        R::require($profile['seat']==='oracle.augur' && is_array($profile['predicate_ids']) && array_is_list($profile['predicate_ids']) && $profile['predicate_ids']!==[],'BASE_PROFILE');
        $profilePredicates=array_values(array_diff($requirements['required_predicates'],CandidateClaim::PREDICATES));
        R::require(R::same($profile['predicate_ids'],$profilePredicates),'BASE_PROFILE_PREDICATES');
        $context+=['required_profile_predicates'=>$profilePredicates,'account_scope'=>R::text($manifest['account_scope']),
            'data_scope'=>$policy['body']['evidence_policy']['data_scope'],'issued_at'=>$policy['created_at']*1000,
            'evaluated_at'=>($evaluatedAt??$store->now())*1000,'expires_at'=>$policy['body']['expires_at']*1000,'universe_coverage'=>'COMPLETE',
            'workload'=>array_map(static fn(string $g):array=>['group_id'=>$g,'input_tokens'=>16384,'generated_tokens'=>4096,'baseline_calls'=>1,'maximum_calls'=>4],['W1','W2','W3']),
            'limits'=>BaseInput::LIMITS,'retryable_failure_allowlist'=>$policy['body']['evidence_policy']['retryable_failure_allowlist']];
        $refs=array_intersect_key($context,array_flip(BaseInput::REFS));$approved=[];$rows=[];
        R::require(is_array($manifest['rows']) && array_is_list($manifest['rows']) && count($manifest['rows'])===count($policy['body']['candidate_bindings']),'BASE_CANDIDATE_COVERAGE');
        $facts=[];
        foreach($manifest['rows'] as $row){R::object($row,['binding_ref','facts_ref']);$key=R::key($row['binding_ref']);R::require(!isset($facts[$key]),'BASE_DUPLICATE_FACTS');
            $f=Policy::content($load(R::ref($row['facts_ref'])),'augur-base-facts');R::object($f,['binding_ref','predicates','bounds','tariffs','contradictions']);
            R::require(R::same($row['binding_ref'],$f['binding_ref']),'BASE_FACTS_SCOPE');$facts[$key]=$f;}
        foreach($policy['body']['candidate_bindings'] as $candidate){
            foreach(['binding_ref','configuration_ref','adapter_ref','mapping_ref'] as $key){$load($candidate[$key]);}
            $config=Policy::content($load($candidate['configuration_ref']),'request-configuration');R::require(R::same($config,Wire::CONFIGURATION),'BASE_CONFIGURATION');
            $mapping=Policy::content($load($candidate['mapping_ref']),'runtime-binding-map');
            R::object($mapping,['snapshot_ref','provider','mappings','adapter_ref','expires_at']);
            R::require($mapping['provider']===$candidate['provider'] && R::same($mapping['adapter_ref'],$candidate['adapter_ref'])
                && $store->now()<R::time($mapping['expires_at']) && $mapping['expires_at']<=$policy['body']['expires_at'],'BASE_MAPPING_SCOPE');
            $load(R::ref($mapping['snapshot_ref']));
            R::require(is_array($mapping['mappings']) && array_is_list($mapping['mappings']) && count($mapping['mappings'])>0 && count($mapping['mappings'])<=256,'BASE_MAPPING_SCOPE');
            $matches=[];$seen=[];
            foreach($mapping['mappings'] as $m){
                R::object($m,['model_ref','dispatch_id','configuration_schema_digest','supported_limits','revision_pin_limitation']);
                $load(R::ref($m['model_ref']));$mk=R::key($m['model_ref']);R::require(!isset($seen[$mk]),'BASE_MAPPING_SCOPE');$seen[$mk]=true;
                \App\Imperium\Runtime\Citadel\Formation\SharedExposure::meters($m['supported_limits'],false);
                if(R::same($m['model_ref'],$candidate['binding_ref'])){$matches[]=$m;}
            }
            R::require(count($matches)===1 && $matches[0]['dispatch_id']===$candidate['model_id']
                && $matches[0]['configuration_schema_digest']===R::hash($config)
                && $matches[0]['revision_pin_limitation']===$candidate['revision_pin'],'BASE_MAPPING_SCOPE');
            $binding=$candidate+['advertised_id'=>$candidate['model_id'],'dispatch_id'=>$candidate['model_id'],'request_config'=>$config];$approved[]=$binding;
            $key=R::key($candidate['binding_ref']);R::require(isset($facts[$key]),'BASE_CANDIDATE_COVERAGE');$f=$facts[$key];unset($facts[$key],$f['binding_ref']);
            $rows[]=['binding'=>$binding,'context_refs'=>$refs]+$f;
        }
        R::require($facts===[],'BASE_CANDIDATE_COVERAGE');$observations=[];
        $evidenceRefs=R::refs($manifest['evidence_refs']);R::require(count($evidenceRefs)>0 && count($evidenceRefs)<=256,'BASE_EVIDENCE_LIMIT');
        foreach($evidenceRefs as $ref){$o=Policy::content($load($ref),'augur-base-observation');
            R::require(!array_key_exists('ref',$o),'BASE_SELF_REFERENCE');
            $load(R::ref($o['source_ref']));$load(R::ref($o['content_ref']));$load(R::ref($o['scope']['profile_ref']));$observations[]=['ref'=>$ref]+$o;
        }
        $input=['schema'=>'imperium.offline-base-selection-input/v1','context'=>$context,'approved_candidates'=>$approved,'candidates'=>$rows,'evidence'=>$observations];
        $parsed=BaseInput::fromArray($input);
        // The verifier receives every exact original consulted, including complete numeric facts and evidence bytes.
        $this->evidence->verify($policy,$input,$originals);
        $snapshot=$input;
        return (new BaseSelector())->propose($parsed);
    }

    public function select(AuthorityStore $store,array $state,array $policy,array $step): array
    {
        $proposal=$this->propose($store,$state,$policy,$step);
        R::require($proposal->status==='PROPOSED_BASE','BASE_NOT_ELIGIBLE');
        return R::refs([$step['input_refs'][1]['ref'],$proposal->proposedBinding['binding_ref']]);
    }
}
