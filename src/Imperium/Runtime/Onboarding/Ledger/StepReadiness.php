<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Ledger;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore,Rules as R};
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class StepReadiness
{
    public static function ready(AuthorityStore $store,array $s,array $policy,string $id): array {
        $matches=array_values(array_filter($policy['body']['steps'],static fn(array $v):bool=>$v['step_id']===$id));R::require(count($matches)===1,'STEP_MISSING');$step=$matches[0];
        foreach($step['depends_on'] as $dep){CompletionResolver::step($store,$s,$policy,$dep);}
        AssessmentGroups::ready($s,$policy,$step);
        foreach($step['input_refs'] as $selector){if($selector['kind']==='public_ref'){$store->checkSource($s,$selector['ref']);}else{AssessmentGroups::success($s,$policy,$selector['group_id']);}}
        return $step;
    }
}
