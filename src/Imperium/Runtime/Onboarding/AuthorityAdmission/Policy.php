<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\AuthorityAdmission;
use App\Imperium\Runtime\Onboarding\Selection\Shape;

/** Closed selected FRESH v1 graph. No runtime source document or expression evaluation. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class Policy
{
    public static function validate(array $h,callable $source): void {
        $b=Rules::object($h['body'],['policy_version','installation_mode','provider','adapter_ref','credential_ref','candidate_bindings','targets','workload_ref','requirements_ref','evidence_policy','limits','allowed_effects','budget_ref','steps','effect_slots','assessment_groups','application_mode','expected_assignments','expires_at']);
        Rules::require($h['schema']==='imperium.operator-bootstrap-policy/v1' && $b['policy_version']==='o2-fresh-v1' && $b['installation_mode']==='FRESH' && $b['provider']==='deepseek' && in_array($b['application_mode'],['A','B'],true),'POLICY_ROUTE');
        $applicationSlots=array_values(array_filter(Shape::list($b['effect_slots']),static fn(mixed $slot):bool=>is_array($slot) && ($slot['effect']??null)==='APPLY_BOOTSTRAP_ASSIGNMENTS'));
        Rules::require(count($applicationSlots)===1 && $applicationSlots[0]['authority_mode']===($b['application_mode']==='A'?'policy_effect':'signed_act'),'POLICY_ROUTE');
        Rules::time($b['expires_at']); Rules::require($b['expires_at']>$h['created_at'] && $b['expires_at']-$h['created_at']<=1800,'POLICY_LIFETIME');
        Rules::require(Rules::same($b['limits'],json_decode(SelectedPolicy::LIMITS,true,512,JSON_THROW_ON_ERROR)),'APPROVED_LIMITS');
        Rules::require(Rules::same($b['evidence_policy'],['freshness_ms'=>json_decode(SelectedPolicy::FRESHNESS,true,512,JSON_THROW_ON_ERROR),'data_scope'=>'PUBLIC_ONLY','retryable_failure_allowlist'=>[]]),'EVIDENCE_POLICY');
        foreach (['adapter_ref','credential_ref','workload_ref','requirements_ref','budget_ref','expected_assignments'] as $k) { $source(Rules::ref($b[$k])); }
        $credential=self::content($source($b['credential_ref']),'credential-binding');
        Rules::object($credential,['authentication','provider','opaque_binding']); Rules::text($credential['opaque_binding']);
        Rules::require($credential['authentication']==='api-key' && $credential['provider']==='deepseek','AUTHENTICATION');
        $requirements=self::content($source($b['requirements_ref']),'requirements');
        Rules::object($requirements,['selection_rule','required_predicates']);
        Rules::require($requirements['selection_rule']==='role_capacity_middle_tier_v1','SELECTION_RULE');
        $predicates=Shape::list($requirements['required_predicates'],true); foreach ($predicates as $id) { Rules::text($id); }
        Rules::require(count(array_unique($predicates))===count($predicates),'PREDICATE_COVERAGE');
        foreach (\App\Imperium\Runtime\Onboarding\ResponseValidation\CandidateClaim::PREDICATES as $required) { Rules::require(in_array($required,$predicates,true),'MANDATORY_PREDICATE'); }
        Rules::require(count($predicates)>count(\App\Imperium\Runtime\Onboarding\ResponseValidation\CandidateClaim::PREDICATES),'PROFILE_PREDICATES');
        $workload=self::content($source($b['workload_ref']),'workload');
        Rules::object($workload,['original_sha256','original_bytes','groups']); Rules::text($workload['original_bytes']);
        // This is the reviewed medium-capacity amended public workload, not regenerated prose.
        Rules::require($workload['original_sha256']==='sha256:0811784aae9263b2507ea1ade05586a64ae9ee8c550559612520a1694945212f','WORKLOAD_IDENTITY');
        Rules::require('sha256:'.hash('sha256',$workload['original_bytes'])===$workload['original_sha256'],'WORKLOAD_BYTES');
        Rules::require(Rules::same($workload['groups'],['W1','W2','W3']),'WORKLOAD_GROUPS');
        $bindings=[]; $models=[];
        foreach (Shape::list($b['candidate_bindings'],true) as $c) {
            Rules::object($c,['binding_ref','provider','model_id','model_version','configuration_ref','adapter_ref','mapping_ref','revision_pin']);
            Rules::require($c['provider']==='deepseek' && in_array($c['model_id'],['deepseek-v4-flash','deepseek-v4-pro'],true) && $c['revision_pin']==='UNAVAILABLE_ACCEPTED_ALIAS','CANDIDATE'); Rules::text($c['model_version']);
            foreach (['binding_ref','configuration_ref','adapter_ref','mapping_ref'] as $k) { $source(Rules::ref($c[$k])); }
            Rules::require(Rules::same($c['adapter_ref'],$b['adapter_ref']),'ADAPTER');
            $key=Rules::key($c['binding_ref']); Rules::require(!isset($bindings[$key]) && !isset($models[$c['model_id']]),'CANDIDATE_DUPLICATE');
            $bindings[$key]=$c; $models[$c['model_id']]=true;
            $config=self::content($source($c['configuration_ref']),'request-configuration');
            Rules::require(Rules::same($config,['max_tokens'=>4096,'stream'=>false,'temperature'=>0,'thinking'=>['type'=>'disabled'],'response_format'=>['type'=>'json_object'],'message_roles'=>['system','user']]),'REQUEST_CONFIGURATION');
        }
        Rules::require(count($bindings)===2 && count($models)===2,'COMPLETE_UNIVERSE');
        $targets=Shape::list($b['targets'],true); Rules::require(count($targets)===2,'TARGETS');
        foreach ($targets as $i=>$target) {
            Rules::object($target,['role','profile_ref','predicate_ids','permitted_bindings']);
            Rules::require($target['role']===['courtyard.courtthane','clavium.locksmith'][$i],'TARGET_ROLE'); $source(Rules::ref($target['profile_ref']));
            Rules::require(Rules::same($target['predicate_ids'],$predicates),'TARGET_PREDICATES');
            $permitted=Rules::refs($target['permitted_bindings']); Rules::require($permitted!==[],'PERMITTED_TUPLES');
            foreach ($permitted as $ref) { Rules::require(isset($bindings[Rules::key($ref)]),'PERMITTED_BINDING'); }
        }
        $assignments=self::content($source($b['expected_assignments']),'expected-assignments');
        Rules::require(is_array($assignments) && array_is_list($assignments) && count($assignments)===2,'ASSIGNMENTS');
        foreach ($assignments as $i=>$row) { Rules::object($row,['role','generation','binding_ref']); Rules::require($row['role']===$targets[$i]['role'],'ASSIGNMENT_ROLE'); Rules::integer($row['generation']); if ($row['binding_ref']!==null) { $source(Rules::ref($row['binding_ref'])); } }
        $graph=json_decode(SelectedPolicy::GRAPH,true,512,JSON_THROW_ON_ERROR);
        foreach (['steps','assessment_groups','effect_slots'] as $key) { self::matchTemplate($graph[$key],$b[$key],$source,$b['expires_at']); }
        $effects=[];
        foreach ($b['effect_slots'] as $slot) {
            Rules::effect($slot['effect'],$slot['authority_mode']); Rules::supported($slot['effect']); $effects[$slot['effect']]=true;
            Rules::require($slot['expires_at']<=$b['expires_at'] && $slot['expires_at']>$h['created_at'],'SLOT_TIME');
            $rule=$slot['terms_rule'];
            if ($rule['kind']==='exact') { Admission::terms($source($rule['object_ref']),$slot['effect']); }
            elseif ($rule['kind']==='eligible_binding') { foreach ($rule['permitted_object_refs'] as $ref) { Admission::terms($source($ref),$slot['effect']); } }
        }
        foreach ($b['assessment_groups'] as $group) { Rules::require(Rules::same($group['workload_ref'],$b['workload_ref']),'GROUP_WORKLOAD'); }
        $allowed=Shape::list($b['allowed_effects'],true); $expected=array_keys($effects); sort($allowed,SORT_STRING); sort($expected,SORT_STRING);
        Rules::require($allowed===$expected,'ALLOWED_EFFECTS');
    }
    private static function matchTemplate(mixed $expected,mixed $actual,callable $source,int $expiry): void {
        if (is_array($expected) && isset($expected['preparation_unresolved_ref'])) {
            if ($expected['preparation_unresolved_ref']==='issue_expiry') { Rules::time($actual); Rules::require($actual<=$expiry,'SLOT_EXPIRY'); return; }
            $source(Rules::ref($actual)); return;
        }
        if (is_array($expected) && isset($expected['preparation_document'])) { $source(Rules::ref($actual)); return; }
        if (is_array($expected)) {
            if (array_is_list($expected)) { $actual=Shape::list($actual); Rules::require(count($actual)===count($expected),'GRAPH_COUNT'); }
            else { $actual=Rules::object($actual,array_keys($expected)); }
            foreach ($expected as $k=>$v) {
                // Concrete public selectors replace template descriptors, preserving tagged boundaries.
                if ($k==='input_refs') {
                    $list=Shape::list($actual[$k]); Rules::require(count($list)===count($v),'INPUT_COVERAGE');
                    foreach ($v as $i=>$selector) {
                        if (isset($selector['preparation_unresolved_ref'])) { Rules::object($list[$i],['kind','ref']); Rules::require($list[$i]['kind']==='public_ref','INPUT_KIND'); $source(Rules::ref($list[$i]['ref'])); }
                        else { self::matchTemplate($selector,$list[$i],$source,$expiry); }
                    }
                } elseif ($k==='authority_mode') { Rules::require(in_array($actual[$k],['signed_act','policy_effect'],true),'AUTHORITY_MODE'); }
                else { self::matchTemplate($v,$actual[$k],$source,$expiry); }
            }
        } else { Rules::require($expected===$actual,'GRAPH_VALUE'); }
    }
    public static function content(array $source,string $kind): mixed {
        Rules::require($source['schema']==='imperium.bootstrap-source/v1' && ($source['body']['kind']??null)===$kind,'SOURCE_KIND');
        return StrictJson::decode($source['body']['content']);
    }
}
