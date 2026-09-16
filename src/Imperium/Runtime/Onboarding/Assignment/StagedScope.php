<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Assignment;

use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules as R,Policy};
use App\Imperium\Runtime\Citadel\Formation\ProfileFitnessContract as F;

/** Pure C1 decoder. Never a policy projection, admission, migration or authority grant. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class StagedScope
{
    public const SCHEMA='imperium.staged-assignment-scope/v1';
    public const FIELDS=['scope_version','founding_policy_ref','founding_completion_ref','establishment_completion_ref','augur_holder_ref',
        'predecessor_scope_ref','expected_application_ref','expected_assignments','stage_generation','targets','permitted_sets','requirements_ref',
        'selection_rule_ref','workload_ref','candidate_bindings','assessment_commissions','application_mode','not_before','expires_at'];
    public static function decode(array $h,array $policy,callable $load): array
    {
        R::record($h); R::record($policy); R::require($h['schema']===self::SCHEMA && $policy['schema']==='imperium.operator-bootstrap-policy/v1','STAGED_SCOPE_DOMAIN');
        $b=R::object($h['body'],self::FIELDS); R::require($b['scope_version']==='ppc10-staged-v1','STAGED_SCOPE_VERSION');
        foreach(['instance_id','citadel_id'] as $field) { R::require($h[$field]===$policy[$field],'STAGED_SCOPE_IDENTITY'); }
        R::require(R::same($b['founding_policy_ref'],R::reference($policy)),'STAGED_FOUNDING_POLICY');
        foreach(['founding_completion_ref','augur_holder_ref','selection_rule_ref'] as $field) { R::ref($b[$field]); }
        R::object($b['establishment_completion_ref'],['schema','id','digest']); R::id($b['establishment_completion_ref']['id']);
        F::nativeDigest($b['establishment_completion_ref']['digest']);
        R::require($b['establishment_completion_ref']['schema']==='imperium.fresh-institutional-completion/v1','STAGED_ESTABLISHMENT_DOMAIN');
        R::require($b['founding_completion_ref']['schema']==='imperium.bootstrap-step-completion/v1'
            && $b['augur_holder_ref']['schema']==='imperium.bootstrap-augur-holder/v1','STAGED_FOUNDING_DOMAIN');
        R::require(is_int($b['stage_generation']) && $b['stage_generation']>=1 && $b['stage_generation']<=4,'STAGED_GENERATION');
        if($b['stage_generation']===1) {
            R::require($b['predecessor_scope_ref']===null && $b['expected_application_ref']===null,'STAGED_INITIAL');
            $initial=Policy::content($load($policy['body']['expected_assignments']),'expected-assignments');
            R::require(R::same($b['expected_assignments'],$initial),'STAGED_INITIAL_PAIR');
        } else {
            R::ref($b['predecessor_scope_ref']); R::ref($b['expected_application_ref']);
            R::require($b['predecessor_scope_ref']['schema']===self::SCHEMA && $b['expected_application_ref']['schema']===StagedApplicationContract::SCHEMA,'STAGED_PREDECESSOR_DOMAIN');
            R::require(is_array($b['expected_assignments']) && array_is_list($b['expected_assignments']) && count($b['expected_assignments'])===2,'STAGED_PRIOR_PAIR');
            foreach($b['expected_assignments'] as $i=>$tuple) { AssignmentRule::settingShape($tuple); R::require($tuple['role']===AssignmentRule::ROLES[$i],'STAGED_PRIOR_PAIR'); }
        }
        foreach(['requirements_ref','workload_ref','candidate_bindings','application_mode'] as $field) { R::require(R::same($b[$field],$policy['body'][$field]),'STAGED_FOUNDING_INPUT'); }
        R::require(R::same($b['selection_rule_ref'],AssignmentRule::slot($policy)['terms_rule']['selection_rule_ref']),'STAGED_SELECTION_RULE');
        R::require(R::time($b['not_before'])<R::time($b['expires_at']) && $policy['created_at']<=$b['not_before'] && $h['created_at']>=$policy['created_at']
            && $b['expires_at']<=$policy['body']['expires_at'],'STAGED_INTERVAL');
        R::require(is_array($b['targets']) && array_is_list($b['targets']) && count($b['targets'])===2,'STAGED_TARGETS');
        foreach($b['targets'] as $i=>$target) {
            R::object($target,['role','profile_ref','profile_generation','permitted_bindings','fitness_contract_ref','fitness_evidence_refs']);
            R::require($target['role']===AssignmentRule::ROLES[$i] && R::integer($target['profile_generation'])>0,'STAGED_TARGET'); R::ref($target['profile_ref']);
            $bindings=R::refs($target['permitted_bindings']); R::require($bindings!==[] && count($bindings)<=2 && R::same($bindings,$target['permitted_bindings']),'STAGED_BINDINGS');
            foreach($bindings as $ref) { R::require(in_array(R::key($ref),array_map(R::key(...),$policy['body']['targets'][$i]['permitted_bindings']),true),'STAGED_BINDING_EXPANSION'); }
            R::ref($target['fitness_contract_ref']); R::require($target['fitness_contract_ref']['schema']===F::CONTRACT,'STAGED_FITNESS_DOMAIN');
            $fitness=R::refs($target['fitness_evidence_refs']); R::require(count($fitness)===7 && R::same($fitness,$target['fitness_evidence_refs']),'STAGED_FITNESS_COVERAGE');
            foreach($fitness as $ref) { R::require($ref['schema']===F::JUDGMENT,'STAGED_FITNESS_DOMAIN'); }
        }
        R::require(is_array($b['permitted_sets']) && array_is_list($b['permitted_sets']) && count($b['permitted_sets'])>0 && count($b['permitted_sets'])<=256,'STAGED_SET_LIMIT');
        $seen=[];
        foreach($b['permitted_sets'] as $pair) {
            self::tuples($pair,$h,$load); $key=R::hash($pair); R::require(!isset($seen[$key]),'STAGED_DUPLICATE_PAIR'); $seen[$key]=true;
        }
        R::require(is_array($b['assessment_commissions']) && array_is_list($b['assessment_commissions']) && count($b['assessment_commissions'])===3,'STAGED_COMMISSIONS');
        foreach($b['assessment_commissions'] as $i=>$commission) {
            R::object($commission,['group_id','constitution_ref','profile_ref','account_scope','account_ref','token_ref','tariff_ref','evidence_refs','not_before','expires_at']);
            R::require($commission['group_id']===['W1','W2','W3'][$i],'STAGED_GROUP_ORDER'); R::text($commission['account_scope']);
            foreach(['constitution_ref','profile_ref','account_ref','token_ref','tariff_ref'] as $field) { R::ref($commission[$field]); }
            if($i>0) { R::require(R::same($commission['profile_ref'],$b['targets'][$i-1]['profile_ref']),'STAGED_COMMISSION_PROFILE'); }
            $refs=R::refs($commission['evidence_refs']); R::require($refs!==[] && count($refs)<=256 && R::same($refs,$commission['evidence_refs']),'STAGED_COMMISSION_EVIDENCE');
            R::require(R::time($commission['not_before'])<R::time($commission['expires_at']) && $b['not_before']<=$commission['not_before'] && $commission['expires_at']<=$b['expires_at'],'STAGED_COMMISSION_INTERVAL');
        }
        return $h;
    }
    public static function tuples(mixed $rows,array $scope,callable $load): void
    {
        R::require($scope['schema']===self::SCHEMA,'STAGED_SCOPE_DOMAIN');
        R::require(is_array($rows) && array_is_list($rows) && count($rows)===2,'STAGED_PAIR');
        foreach($rows as $i=>$tuple) {
            R::object($tuple,AssignmentRule::TUPLE_FIELDS); $target=$scope['body']['targets'][$i];
            R::require($tuple['role']===AssignmentRule::ROLES[$i] && R::same($tuple['profile_ref'],$target['profile_ref'])
                && $tuple['profile_generation']===$target['profile_generation'] && R::integer($tuple['binding_generation'])>0,'STAGED_TUPLE_TARGET');
            $matches=array_values(array_filter($scope['body']['candidate_bindings'],static fn(array $b):bool=>R::same($b['binding_ref'],$tuple['binding_ref'])));
            R::require(count($matches)===1,'STAGED_TUPLE_BINDING');
            foreach(['provider','model_id','model_version','configuration_ref'] as $field) { R::require(R::same($matches[0][$field],$tuple[$field]),'STAGED_TUPLE_IDENTITY'); }
            R::require(in_array(R::key($tuple['binding_ref']),array_map(R::key(...),$target['permitted_bindings']),true),'STAGED_TUPLE_PERMISSION');
            foreach(['binding_ref','configuration_ref','profile_ref'] as $field) { $load(R::ref($tuple[$field])); }
        }
    }
}
