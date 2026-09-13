<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Assignment;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore,Rules as R};
use App\Imperium\Runtime\Onboarding\Ledger\{LedgerState,StateMigration};

/** Explicit schema transition. Inspection never invokes this owner. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class AssignmentMigration
{
    public const SCHEMA='imperium.onboarding-authority-state/v4';
    public function __construct(private AuthorityStore $store) {}

    public function migrate(array $expectedHead):array
    {
        R::head($expectedHead);
        return $this->store->journal->changeAtHead(function(array &$state,array $head)use($expectedHead):array {
            $s=$this->store->state($state);
            if($s['schema']===self::SCHEMA){return $s['assignment_migration'];}
            R::require($s['schema']==='imperium.onboarding-authority-state/v3' && R::same($head,$expectedHead),'ASSIGNMENT_MIGRATION_HEAD');
            $trust=$this->store->currentTrust($s);self::quiescent($s);
            R::require(strlen(CanonicalJson::encode($s))<=8388608,'ASSIGNMENT_MIGRATION_LIMIT');
            $record=$this->store->make('imperium.bootstrap-assignment-migration/v1','assignment-migration-'.substr(R::hash($s),7,24),[
                'from_schema'=>$s['schema'],'to_schema'=>self::SCHEMA,'predecessor_head'=>$head,
                'prior_state'=>$s,'prior_digest'=>R::hash($s)],[$trust['enrollment_receipt_ref']]);
            $s['schema']=self::SCHEMA;$s['assignment_migration']=$record;
            LedgerState::validate($s);$state['onboarding']=$s;return $record;
        });
    }

    private static function quiescent(array $s):void
    {
        R::require($s['source_fences']===[],'ASSIGNMENT_MIGRATION_IN_FLIGHT');
        foreach($s['claims'] as $claim){R::require($claim['settled']!==null,'ASSIGNMENT_MIGRATION_IN_FLIGHT');}
    }

    public static function validate(array $s):void
    {
        $h=R::record($s['assignment_migration']);
        $b=R::object($h['body'],['from_schema','to_schema','predecessor_head','prior_state','prior_digest']);
        R::require($h['schema']==='imperium.bootstrap-assignment-migration/v1'
            && $h['producer']['service']==='onboarding.authority-admission'
            && $h['instance_id']===$s['trust']['instance_id'] && $h['citadel_id']===$s['trust']['citadel_id']
            && $b['from_schema']==='imperium.onboarding-authority-state/v3' && $b['to_schema']===self::SCHEMA
            && $s['schema']===self::SCHEMA,'ASSIGNMENT_MIGRATION_SCHEMA');
        R::head($b['predecessor_head']);
        R::require($b['predecessor_head']['generation']>0 && is_array($b['prior_state'])
            && ($b['prior_state']['schema']??null)===$b['from_schema'],'ASSIGNMENT_MIGRATION_SCHEMA');
        R::require(strlen(CanonicalJson::encode($b['prior_state']))<=8388608
            && R::hash($b['prior_state'])===$b['prior_digest'],'ASSIGNMENT_MIGRATION_DIGEST');
        $prior=$b['prior_state'];LedgerState::validate($prior);self::quiescent($prior);
        R::require(R::same($h['sources'],[$prior['trust']['body']['enrollment_receipt_ref']]),'ASSIGNMENT_MIGRATION_SOURCE');
        foreach(['trust','migration','augur_migration'] as $field){R::require(R::same($s[$field],$prior[$field]),'ASSIGNMENT_MIGRATION_ORIGINAL');}
        foreach(['acts','policies','evidence','revocations','admissions',...StateMigration::MAPS] as $map){
            foreach($prior[$map] as $key=>$original){
                R::require(isset($s[$map][$key]),'ASSIGNMENT_MIGRATION_LOST_ORIGINAL');$current=$s[$map][$key];
                if($map==='sequences'){unset($original['head'],$current['head']);}
                R::require(R::same($original,$current),'ASSIGNMENT_MIGRATION_CHANGED_ORIGINAL');
            }
        }
    }
}
