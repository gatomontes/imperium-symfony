<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Assignment;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules as R;

/** Closed successor records, not an application producer or current verifier. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class StagedApplicationContract
{
    public const SCHEMA='imperium.staged-assignment-application/v1';
    public const TERMS='imperium.staged-assignment-terms/v1';
    public const VIEW='imperium.staged-assignment-assessment-view/v1';
    public static function terms(array $h): array
    {
        R::record($h); R::require($h['schema']===self::TERMS,'STAGED_TERMS_DOMAIN');
        $b=R::object($h['body'],['scope_ref','assessment_view_ref','expected_application_ref','prior_assignments','next_set_digest']);
        self::links($b); R::digest($b['next_set_digest']); self::prior($b['prior_assignments'],$b['expected_application_ref']===null); return $h;
    }
    public static function receipt(array $h): array
    {
        R::record($h); R::require($h['schema']===self::SCHEMA,'STAGED_APPLICATION_DOMAIN');
        $b=R::object($h['body'],['founding_policy_ref','scope_ref','assessment_view_ref','previous_application_ref','authority','prior_assignments','next_assignments','consumed_effect_ids','commit_head']);
        self::links(['scope_ref'=>$b['scope_ref'],'assessment_view_ref'=>$b['assessment_view_ref'],'expected_application_ref'=>$b['previous_application_ref']]);
        R::ref($b['founding_policy_ref']); R::require($b['founding_policy_ref']['schema']==='imperium.operator-bootstrap-policy/v1','STAGED_FOUNDING_POLICY');
        self::prior($b['prior_assignments'],$b['previous_application_ref']===null);
        R::require(is_array($b['next_assignments']) && array_is_list($b['next_assignments']) && count($b['next_assignments'])===2,'STAGED_PAIR');
        foreach($b['next_assignments'] as $i=>$row) { AssignmentRule::settingShape($row); R::require($row['role']===AssignmentRule::ROLES[$i] && $row['generation']===$b['prior_assignments'][$i]['generation']+1,'STAGED_SETTINGS_GENERATION'); }
        R::head($b['commit_head']); R::require(is_array($b['consumed_effect_ids']) && array_is_list($b['consumed_effect_ids']) && count($b['consumed_effect_ids'])===1,'STAGED_CONSUMPTION'); R::digest($b['consumed_effect_ids'][0]);
        $a=$b['authority']; R::require(is_array($a),'STAGED_AUTHORITY');
        if(($a['kind']??null)==='scope_effect') {
            R::object($a,['kind','scope_act_ref','scope_admission_ref','scope_ref','assessment_view_ref','next_set_digest']);
            R::require($b['previous_application_ref']===null && R::same($a['scope_ref'],$b['scope_ref']) && R::same($a['assessment_view_ref'],$b['assessment_view_ref']),'STAGED_SCOPE_EFFECT');
            foreach(['scope_act_ref','scope_admission_ref'] as $field) { R::ref($a[$field]); } R::digest($a['next_set_digest']);
        } else { R::object($a,['kind','act_ref','admission_ref']); R::require($a['kind']==='signed_act','STAGED_AUTHORITY'); R::ref($a['act_ref']); R::ref($a['admission_ref']); }
        return $h;
    }
    private static function links(array $b): void
    {
        R::ref($b['scope_ref']); R::ref($b['assessment_view_ref']); R::require($b['scope_ref']['schema']===StagedScope::SCHEMA && $b['assessment_view_ref']['schema']===self::VIEW,'STAGED_TERMS_LINK');
        if($b['expected_application_ref']!==null) { R::ref($b['expected_application_ref']); R::require($b['expected_application_ref']['schema']===self::SCHEMA,'STAGED_PREDECESSOR_DOMAIN'); }
    }
    private static function prior(array $rows,bool $initial): void
    {
        R::require(array_is_list($rows) && count($rows)===2,'STAGED_PRIOR_PAIR');
        foreach($rows as $i=>$row) {
            if($initial) { R::require(R::same($row,['role'=>AssignmentRule::ROLES[$i],'generation'=>0,'binding_ref'=>null]),'STAGED_INITIAL_PAIR'); }
            else { AssignmentRule::settingShape($row); R::require($row['role']===AssignmentRule::ROLES[$i],'STAGED_PRIOR_PAIR'); }
        }
    }
}
