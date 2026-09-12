<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Assignment;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules as R,Policy,Admission};
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class ChangeAuthority
{
    public static function initialTerms(array $policy,array $object,callable $load):bool
    {
        foreach(AssignmentRule::sets($policy,$load) as $set){if(R::same(R::reference($set['terms']),R::reference($object))){return true;}}
        return false;
    }
    public static function scope(array $policy,array $terms,callable $load):array
    {
        Admission::terms($terms,'APPLY_BOOTSTRAP_ASSIGNMENTS');
        R::require($terms['body']['required_completed_refs']===[],'CHANGE_COMPLETION_SCOPE');
        $body=Policy::content($load($terms['body']['terms']),'assignment-change');
        R::object($body,['expected_application_ref','prior_assignments','next_set_ref','assessment_view_ref']);
        R::ref($body['expected_application_ref']);R::ref($body['assessment_view_ref']);R::ref($body['next_set_ref']);
        R::require($body['expected_application_ref']['schema']==='imperium.bootstrap-assignment-application/v1'
            && $body['assessment_view_ref']['schema']==='imperium.bootstrap-assessment-view/v1','CHANGE_PREDECESSOR');
        $sets=AssignmentRule::sets($policy,$load);R::require(isset($sets[R::key($body['next_set_ref'])]),'CHANGE_SET_SCOPE');
        R::require(is_array($body['prior_assignments']) && array_is_list($body['prior_assignments']) && count($body['prior_assignments'])===2,'CHANGE_PAIR');
        foreach($body['prior_assignments'] as $i=>$row){R::object($row,[...AssignmentRule::TUPLE_FIELDS,'generation']);R::require($row['role']===AssignmentRule::ROLES[$i] && R::integer($row['generation'])>0,'CHANGE_GENERATION');}
        return $body;
    }
}
