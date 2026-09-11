<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Ledger;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore,Rules as R};
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class StateMigration
{
    public const MAPS=['bindings','sequences','commands','steps','slots','claims','applications','source_fences','attempt_outcomes','group_inputs','assessment_views','budget_bindings'];
    public function __construct(private AuthorityStore $store) {}
    public function migrate(array $expectedHead): array {
        R::head($expectedHead);
        return $this->store->journal->changeAtHead(function(array &$state,array $head)use($expectedHead):array {
            $s=$this->store->state($state);
            if ($s['schema']==='imperium.onboarding-authority-state/v2') { return $s['migration']; }
            R::require(R::same($head,$expectedHead),'STALE_HEAD');$trust=$this->store->currentTrust($s);
            $preserved=$s;unset($preserved['schema']);
            $record=$this->store->make('imperium.bootstrap-state-migration/v1','migration-'.substr(R::hash($s),7,24),[
                'from_schema'=>$s['schema'],'to_schema'=>'imperium.onboarding-authority-state/v2','predecessor_head'=>$head,
                'prior_subtree_digest'=>R::hash($s),'preserved_maps_digest'=>R::hash($preserved)],[$trust['enrollment_receipt_ref']]);
            $s['schema']='imperium.onboarding-authority-state/v2';$s['migration']=$record;
            foreach(self::MAPS as $map){$s[$map]=[];} LedgerState::validate($s);$state['onboarding']=$s;return $record;
        });
    }
}
