<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Augur;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore,Rules as R};
use App\Imperium\Runtime\Onboarding\Ledger\{LedgerState,StateMigration};
use App\Bootstrap\CanonicalJson;

#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class AugurMigration
{
    public function __construct(private AuthorityStore $store){}
    public function migrate(array $expectedHead):array
    {
        R::head($expectedHead);
        return $this->store->journal->changeAtHead(function(array &$state,array $head)use($expectedHead):array{
            $s=$this->store->state($state);
            if($s['schema']==='imperium.onboarding-authority-state/v3'){return $s['augur_migration'];}
            R::require($s['schema']==='imperium.onboarding-authority-state/v2' && R::same($head,$expectedHead),'AUGUR_MIGRATION_HEAD');
            $trust=$this->store->currentTrust($s);self::quiescent($s);
            R::require(strlen(CanonicalJson::encode($s))<=4194304,'AUGUR_MIGRATION_LIMIT');
            $record=$this->store->make('imperium.bootstrap-augur-migration/v1','augur-migration-'.substr(R::hash($s),7,24),[
                'from_schema'=>$s['schema'],'to_schema'=>'imperium.onboarding-authority-state/v3','predecessor_head'=>$head,
                'prior_state'=>$s,'prior_digest'=>R::hash($s)],[$trust['enrollment_receipt_ref']]);
            $s['schema']='imperium.onboarding-authority-state/v3';$s['augur_migration']=$record;
            LedgerState::validate($s);$state['onboarding']=$s;return $record;
        });
    }
    private static function quiescent(array $s):void
    {
        R::require($s['source_fences']===[],'AUGUR_MIGRATION_IN_FLIGHT');
        foreach($s['claims'] as $claim){R::require($claim['settled']!==null,'AUGUR_MIGRATION_IN_FLIGHT');}
        foreach($s['steps'] as $step){R::require($step['completion']!==null,'AUGUR_MIGRATION_IN_FLIGHT');}
    }
    public static function validate(array $s):void
    {
        $h=R::record($s['augur_migration']);$b=R::object($h['body'],['from_schema','to_schema','predecessor_head','prior_state','prior_digest']);
        R::require($h['schema']==='imperium.bootstrap-augur-migration/v1' && $h['producer']['service']==='onboarding.authority-admission'
            && $h['instance_id']===$s['trust']['instance_id'] && $h['citadel_id']===$s['trust']['citadel_id']
            && $b['from_schema']==='imperium.onboarding-authority-state/v2' && $b['to_schema']===$s['schema'],'AUGUR_MIGRATION_SCHEMA');
        R::head($b['predecessor_head']);R::require($b['prior_digest']===R::hash($b['prior_state']) && strlen(CanonicalJson::encode($b['prior_state']))<=4194304,'AUGUR_MIGRATION_DIGEST');
        $prior=$b['prior_state'];R::require($prior['schema']===$b['from_schema'],'AUGUR_MIGRATION_SCHEMA');LedgerState::validate($prior);self::quiescent($prior);
        R::require(R::same($h['sources'],[$prior['trust']['body']['enrollment_receipt_ref']]) && R::same($s['trust'],$prior['trust']) && R::same($s['migration'],$prior['migration']),'AUGUR_MIGRATION_ORIGINAL');
        foreach(['acts','policies','evidence','revocations','admissions',...StateMigration::MAPS] as $map){
            foreach($prior[$map] as $key=>$value){
                R::require(isset($s[$map][$key]),'AUGUR_MIGRATION_LOST_ORIGINAL');$current=$s[$map][$key];
                if($map==='sequences'){unset($value['head'],$current['head']);}
                R::require(R::same($value,$current),'AUGUR_MIGRATION_CHANGED_ORIGINAL');
            }
        }
    }
}
