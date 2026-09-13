<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Assignment;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules as R,Policy,Admission,StrictJson};
use App\Imperium\Runtime\Onboarding\Ledger\{AssessmentGroups,ResponseEvidence};
use App\Imperium\Runtime\Onboarding\ResponseValidation\{ExpectedContext,ParsedResponse};
use App\Imperium\Runtime\Onboarding\Selection\{SelectionRule,AssignmentSelector,RoleInput,Candidate,RecordRef};

/** Bounded structural derivation. This class never establishes live authority. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class AssignmentRule
{
    public const ROLES=['courtyard.courtthane','clavium.locksmith'];
    public const VIEW_FIELDS=['policy_ref','group_outcome_refs','lineage_refs','predicate_results','permitted_assignment_set_ref','selection_rule_ref','selection_evidence'];
    public const RECEIPT_FIELDS=['policy_ref','result_ref','authority','prior_assignments','next_assignments','consumed_effect_ids','commit_head'];
    public const TUPLE_FIELDS=['role','provider','model_id','model_version','binding_ref','configuration_ref','profile_ref','profile_generation','binding_generation'];
    public static function settingShape(mixed $tuple):void
    {
        R::object($tuple,[...self::TUPLE_FIELDS,'generation']);R::require(in_array($tuple['role'],self::ROLES,true),'SETTINGS_ROLE');
        foreach(['provider','model_id','model_version'] as $field){R::text($tuple[$field]);}
        foreach(['binding_ref','configuration_ref','profile_ref'] as $field){R::ref($tuple[$field]);}
        foreach(['profile_generation','binding_generation','generation'] as $field){R::require(R::integer($tuple[$field])>0,'SETTINGS_GENERATION');}
    }

    public static function slot(array $policy):array
    {
        $slots=array_values(array_filter($policy['body']['effect_slots'],static fn(array $s):bool=>$s['effect']==='APPLY_BOOTSTRAP_ASSIGNMENTS'));
        R::require(count($slots)===1,'ASSIGNMENT_SLOT');$slot=$slots[0];
        R::object($slot['terms_rule'],['kind','permitted_tuple_set_ref','result_group_ids','required_predicates_ref','expected_assignments_ref','selection_rule_ref']);
        R::require($slot['slot_id']==='slot.apply-assignments' && $slot['terms_rule']['kind']==='assessed_assignment_set'
            && $slot['terms_rule']['result_group_ids']===['W1','W2','W3'],'ASSIGNMENT_RULE');
        R::require(R::same($slot['terms_rule']['expected_assignments_ref'],$policy['body']['expected_assignments'])
            && R::same($slot['terms_rule']['required_predicates_ref'],$policy['body']['requirements_ref']),'ASSIGNMENT_RULE_SCOPE');
        R::require(($policy['body']['application_mode']==='A' && $slot['authority_mode']==='policy_effect')
            || ($policy['body']['application_mode']==='B' && $slot['authority_mode']==='signed_act'),'APPLICATION_AUTHORITY_MODE');
        return $slot;
    }

    public static function sets(array $policy,callable $load):array
    {
        $rule=self::slot($policy)['terms_rule'];$h=$load($rule['selection_rule_ref']);
        R::require($h['schema']==='imperium.bootstrap-assignment-selection-rule/v1','ASSIGNMENT_RULE_SOURCE');SelectionRule::decode($h['body']);
        $permission=Policy::content($load($rule['permitted_tuple_set_ref']),'permitted-assignment-sets');R::object($permission,['sets']);
        R::require(is_array($permission['sets']) && array_is_list($permission['sets']) && count($permission['sets'])>0 && count($permission['sets'])<=256,'ASSIGNMENT_SET_LIMIT');
        $sets=[];$pairs=[];
        foreach($permission['sets'] as $row){
            R::object($row,['set_ref','terms_ref']);$set=$load(R::ref($row['set_ref']));$body=Policy::content($set,'assignment-set');
            R::object($body,['assignments','profile_evidence_ref']);self::tuples($body['assignments'],$policy,$load);
            $load(R::ref($body['profile_evidence_ref']));$terms=$load(R::ref($row['terms_ref']));Admission::terms($terms,'APPLY_BOOTSTRAP_ASSIGNMENTS');
            R::require(R::same($terms['body']['terms'],$row['set_ref']) && $terms['body']['required_completed_refs']===[],'ASSIGNMENT_TERMS_SCOPE');
            $pair=R::hash(array_column($body['assignments'],'binding_ref'));
            R::require(!isset($pairs[$pair]) && !isset($sets[R::key($row['set_ref'])]),'ASSIGNMENT_SET_DUPLICATE');$pairs[$pair]=true;
            $sets[R::key($row['set_ref'])]=['set'=>$set,'terms'=>$terms,'assignments'=>$body['assignments'],'profile_evidence_ref'=>$body['profile_evidence_ref']];
        }
        return $sets;
    }

    public static function tuples(mixed $rows,array $policy,callable $load):void
    {
        R::require(is_array($rows) && array_is_list($rows) && count($rows)===2,'ASSIGNMENT_PAIR');
        foreach($rows as $i=>$row){
            R::object($row,self::TUPLE_FIELDS);$target=$policy['body']['targets'][$i];
            R::require($row['role']===self::ROLES[$i] && R::same($row['profile_ref'],$target['profile_ref']),'ASSIGNMENT_PROFILE');
            R::integer($row['profile_generation']);R::integer($row['binding_generation']);
            R::require($row['profile_generation']>0 && $row['binding_generation']>0,'ASSIGNMENT_GENERATION');
            $matches=array_values(array_filter($policy['body']['candidate_bindings'],static fn(array $b):bool=>R::same($b['binding_ref'],$row['binding_ref'])));
            R::require(count($matches)===1,'ASSIGNMENT_BINDING');$binding=$matches[0];
            foreach(['provider','model_id','model_version','configuration_ref'] as $field){R::require(R::same($row[$field],$binding[$field]),'ASSIGNMENT_IDENTITY');}
            R::require(in_array(R::key($row['binding_ref']),array_map(R::key(...),$target['permitted_bindings']),true),'ASSIGNMENT_NOT_PERMITTED');
            foreach(['binding_ref','configuration_ref','profile_ref'] as $field){$load(R::ref($row[$field]));}
        }
    }

    public static function parsed(array $s,array $policy):array
    {
        $groups=[];$lineage=[];
        foreach(['W1','W2','W3'] as $id){
            foreach(AssessmentGroups::group($policy,$id)['attempt_step_ids'] as $step){$o=AssessmentGroups::outcome($s,$policy,$step);if($o!==null){$lineage[]=R::reference($o);}}
            $o=AssessmentGroups::success($s,$policy,$id);$claims=array_values(array_filter($s['claims'],static fn(array $c):bool=>R::same(R::reference($c['record']),$o['claim_ref'])));
            R::require(count($claims)===1 && count($claims[0]['custody'])===5 && $claims[0]['settled']!==null,'ASSIGNMENT_ORIGINAL');
            $claim=$claims[0];$checkpoint=$claim['custody'][4];$envelope=ResponseEvidence::envelope($checkpoint['body']['response_envelope'],$claim,$claim['custody'][3]['body']['response_metadata']);
            $wire=StrictJson::decode($claim['operation']['prepared']['wire']);$input=StrictJson::decode($wire['messages'][1]['content']);$p=$input['input'];
            $expected=ExpectedContext::fromInputs($id,R::hash($p),$p['holder']['ref'],R::reference($p['profile']),array_column($policy['body']['candidate_bindings'],'binding_ref'),array_map(R::reference(...),$p['evidence']));
            $raw=StrictJson::decode($envelope['response']);$choice=$raw['choices'][0];R::require($choice['finish_reason']==='stop','ASSIGNMENT_RESPONSE');
            $parsed=ParsedResponse::parse($choice['message']['content'],$expected);
            $groups[]=['outcome'=>$o,'checkpoint'=>$checkpoint,'response'=>$parsed];
        }
        return ['policy'=>$policy,'groups'=>$groups,'lineage'=>$lineage];
    }

    public static function derive(array $s,array $policy,array $slot,callable $load):array
    {
        R::require(R::same($slot,self::slot($policy)),'ASSIGNMENT_SLOT');
        return self::select(self::parsed($s,$policy),$load);
    }

    public static function select(array $originals,callable $load):array
    {
        $policy=$originals['policy'];$rule=self::slot($policy)['terms_rule'];$sets=self::sets($policy,$load);$inputs=[];$predicates=[];
        $providerFit=[];foreach($originals['groups'][0]['response']->candidateRows as $row){if($row->fit==='FIT'){$providerFit[$row->bindingRef->key()]=true;}}
        foreach($policy['body']['targets'] as $i=>$target){
            $response=$originals['groups'][$i+1]['response'];R::require($response->profileRef->digest===$target['profile_ref']['digest'],'ASSIGNMENT_PROFILE');
            $fitting=[];
            foreach($response->candidateRows as $row){
                // Keep the entire role FIT set for rank coverage. Provider findings
                // filter permissions only after complete role order validation.
                if($row->fit!=='FIT'){continue;}
                $binding=array_values(array_filter($policy['body']['candidate_bindings'],static fn(array $b):bool=>$b['binding_ref']['digest']===$row->bindingRef->digest))[0];
                $fitting[]=Candidate::decode(['ref'=>$binding['binding_ref'],'identity'=>['provider'=>$binding['provider'],'model_id'=>$binding['model_id'],'model_version'=>$binding['model_version'],
                    'configuration_digest'=>$binding['configuration_ref']['digest'],'profile_digest'=>$target['profile_ref']['digest'],'binding_digest'=>$binding['binding_ref']['digest']]]);
                $predicates[]=['role'=>$target['role'],'binding_ref'=>$binding['binding_ref'],'response_ref'=>R::reference($originals['groups'][$i+1]['checkpoint']),
                    'predicates'=>array_map(static fn($p):string=>$p->disposition,$row->predicates)];
            }
            $allowed=[];foreach($target['permitted_bindings'] as $ref){$typed=RecordRef::decode($ref);if(isset($providerFit[$typed->key()])){$allowed[]=$typed;}}
            $inputs[]=RoleInput::fromValidatedInputs($target['role'],$fitting,$allowed,$response->capacityOrder,$response->evidenceRefs);
        }
        $pairs=[];foreach($sets as $set){$pairs[]=['courtthane'=>RecordRef::decode($set['assignments'][0]['binding_ref']),'locksmith'=>RecordRef::decode($set['assignments'][1]['binding_ref'])];}
        $selected=(new AssignmentSelector())->selectSet(SelectionRule::decode($load($rule['selection_rule_ref'])['body']),$inputs[0],$inputs[1],$pairs);
        R::require($selected->status==='SELECTED',$selected->status);$results=[$selected->courtthane,$selected->locksmith];$chosen=null;$evidence=[];
        foreach($sets as $set){if($set['assignments'][0]['binding_ref']['digest']===$results[0]->selected->ref->digest && $set['assignments'][1]['binding_ref']['digest']===$results[1]->selected->ref->digest){$chosen=$set;}}
        R::require($chosen!==null,'ASSIGNMENT_SET');
        $refArray=static fn(RecordRef $r):array=>['schema'=>$r->schema,'id'=>$r->id,'digest'=>$r->digest];
        foreach($results as $i=>$result){$ref=R::reference($originals['groups'][$i+1]['checkpoint']);$evidence[]=['role'=>self::ROLES[$i],'response_ref'=>$ref,'eligible_binding_refs'=>array_map($refArray,$result->eligible),
            'capacity_order_ref'=>$ref,'selected_tier_index'=>$result->tierIndex,'selected_binding_ref'=>$refArray($result->selected->ref)];}
        $outcomes=array_map(static fn(array $g):array=>R::reference($g['outcome']),$originals['groups']);
        $body=['policy_ref'=>R::reference($policy),'group_outcome_refs'=>$outcomes,'lineage_refs'=>$originals['lineage'],'predicate_results'=>['common'=>$predicates,'profile'=>self::profileEvidence($policy,$chosen,$load)],
            'permitted_assignment_set_ref'=>R::reference($chosen['set']),'selection_rule_ref'=>$rule['selection_rule_ref'],'selection_evidence'=>$evidence];
        return ['terms_ref'=>R::reference($chosen['terms']),'derivation_input_refs'=>R::refs([...$outcomes,R::reference($chosen['set']),$rule['selection_rule_ref']]),'view_body'=>$body,'selected'=>$chosen];
    }

    public static function profileEvidence(array $policy,array $set,callable $load):array
    {
        $source=$load($set['profile_evidence_ref']);$body=Policy::content($source,'assignment-profile-evidence');R::object($body,['rows']);
        R::require(is_array($body['rows']) && array_is_list($body['rows']) && count($body['rows'])<=256,'PROFILE_EVIDENCE_ROWS');$out=[];$seen=[];
        $required=Policy::content($load($policy['body']['requirements_ref']),'requirements')['required_predicates'];
        $extra=array_values(array_diff($required,\App\Imperium\Runtime\Onboarding\ResponseValidation\CandidateClaim::PREDICATES));
        foreach($body['rows'] as $row){
            R::object($row,['role','binding_ref','profile_ref','profile_generation','binding_generation','predicates']);
            R::require(in_array($row['role'],self::ROLES,true),'PROFILE_EVIDENCE_ROLE');R::ref($row['binding_ref']);R::ref($row['profile_ref']);R::integer($row['profile_generation']);R::integer($row['binding_generation']);
            $key=R::hash([$row['role'],$row['binding_ref'],$row['profile_ref']]);R::require(!isset($seen[$key]),'PROFILE_EVIDENCE_DUPLICATE');$seen[$key]=true;
            R::object($row['predicates'],$extra);
            foreach($row['predicates'] as $p){R::object($p,['disposition','evidence_refs']);R::require(in_array($p['disposition'],['PASS','FAIL','UNKNOWN'],true),'PROFILE_PREDICATE');R::require(R::refs($p['evidence_refs'])!==[],'PROFILE_EVIDENCE_MISSING');foreach($p['evidence_refs'] as $ref){$load($ref);}}
        }
        foreach($set['assignments'] as $tuple){
            $rows=array_values(array_filter($body['rows'],static fn(array $row):bool=>$row['role']===$tuple['role'] && R::same($row['binding_ref'],$tuple['binding_ref']) && R::same($row['profile_ref'],$tuple['profile_ref'])));
            R::require(count($rows)===1,'PROFILE_PREDICATE_MISSING');$row=$rows[0];
            R::require($row['profile_generation']===$tuple['profile_generation'] && $row['binding_generation']===$tuple['binding_generation'],'PROFILE_EVIDENCE_GENERATION');
            foreach($row['predicates'] as $p){R::require($p['disposition']==='PASS','PROFILE_PREDICATE_REFUSED');}
            $out[]=['source_ref'=>R::reference($source),...$row];
        }
        return $out;
    }
}
