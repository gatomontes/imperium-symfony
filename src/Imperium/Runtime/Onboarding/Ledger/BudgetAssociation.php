<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Ledger;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore,Rules as R,Policy};
use App\Imperium\Runtime\Citadel\Formation\{FormationSignatures,FormationJournal as J};
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class BudgetAssociation
{
    public static function resolve(AuthorityStore $store,array $state,array $policy): array {
        $s=$store->state($state);$source=$store->checkSource($s,$policy['body']['budget_ref']);
        $b=Policy::content($source,'shared-budget');R::object($b,['schema','lineage_ref','limits','formation_sources']);
        R::require($b['schema']==='imperium.bootstrap-budget-association/v1','SHARED_BUDGET_SOURCE_UNRESOLVED');
        $root=$store->checkSource($s,$b['lineage_ref']);foreach([$source,$root] as $original){$entry=$s['evidence'][R::key(R::reference($original))];R::require($entry['admission_key']!==null,'BUDGET_ORIGINAL_ADMISSION');$origin=\App\Imperium\Runtime\Onboarding\AuthorityAdmission\Admission::retained($s,$entry['admission_key']);$store->checkSource($s,R::reference($origin['object']));}$rootBody=Policy::content($root,'budget-root');R::object($rootBody,['schema','limits']);
        R::require($rootBody['schema']==='imperium.bootstrap-budget-root/v1' && R::same($rootBody['limits'],$b['limits']),'BUDGET_LINEAGE');
        \App\Imperium\Runtime\Citadel\Formation\SharedExposure::meters($b['limits'],false);
        R::require($b['limits']['calls']<=13 && $b['limits']['cost_microusd']<=1200000 && $b['limits']['milliseconds']<=730000 && $b['limits']['input_tokens']<=196608 && $b['limits']['output_tokens']<=49152,'BUDGET_SCOPE');
        R::require(is_array($b['formation_sources']) && array_is_list($b['formation_sources']) && count($b['formation_sources'])<=256,'BUDGET_SOURCES');$bindings=[];$seen=[];
        foreach($b['formation_sources'] as $item){
            R::object($item,['session_id','resource_decision_digest']);R::require(!isset($seen[$item['session_id']]),'DUPLICATE_BUDGET_SOURCE');$seen[$item['session_id']]=true;
            $session=$state['sessions'][$item['session_id']]??null;R::require(is_array($session),'SHARED_BUDGET_SOURCE_UNRESOLVED');
            (new FormationSignatures($store->journal,$store->clock))->verify($state,$session['decision'],$session['effect'],$session['terms']);
            R::require($item['resource_decision_digest']===J::digest($session['provider_resource_decision']),'BUDGET_RESOURCE_IDENTITY');
            $bindings[]=['kind'=>'formation',...$item];
        }
        $bindings[]=['kind'=>'onboarding','policy_ref'=>R::reference($policy)];
        return ['identity'=>R::hash([$store->instance,$store->citadel,R::reference($root)]),'limits'=>$b['limits'],'root_ref'=>R::reference($root),'source_bindings'=>$bindings];
    }
}
