<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Ledger;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore,Rules as R};
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class CompletionResolver
{
    public static function resolve(AuthorityStore $store,array $s,array $policy,array $obligations): void {
        foreach($obligations as $o){
            if($o['kind']==='completed_step'){self::step($store,$s,$policy,$o['step_id']);}
            elseif($o['kind']==='completed_ref'){
                $matches=array_filter($s['steps'],static fn(array $v):bool=>$v['completion']!==null && R::same(R::reference($v['completion']),$o['ref']));
                R::require(count($matches)===1,'COMPLETION_ORIGINAL_MISSING');$entry=array_values($matches)[0];R::require($entry['key'][1]===$policy['record_digest'],'COMPLETION_POLICY');self::step($store,$s,$policy,$entry['key'][2]);
            }elseif($o['kind']!=='source_effect'){throw new \RuntimeException('O2_UNKNOWN_OBLIGATION');}
        }
    }

    public static function step(AuthorityStore $store,array $s,array $policy,string $id,array $path=[]):array {
        R::require(count($path)<=32 && !isset($path[$id]),'COMPLETION_CYCLE');$path[$id]=true;
        $entry=LedgerState::step($s,$policy,$id);$matches=array_values(array_filter($policy['body']['steps'],static fn(array $v):bool=>$v['step_id']===$id));R::require(count($matches)===1,'COMPLETION_POLICY');$step=$matches[0];
        foreach($step['depends_on'] as $dep){self::step($store,$s,$policy,$dep,$path);}
        if($step['effect_slot_id']!==null){
            [,,$facts]=(new CommandLedger($store))->authority($s,$policy,$step);
            foreach($facts['terms']['sources'] as $ref){$store->checkSource($s,$ref);}
        }
        return $entry;
    }
}
