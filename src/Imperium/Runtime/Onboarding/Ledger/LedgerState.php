<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Ledger;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules as R;
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class LedgerState
{
    public static function key(string $tag,array $tuple): string { return R::hash([$tag,...$tuple]); }
    public static function h(array $h,string $name): array { R::record($h);R::require($h['schema']==='imperium.bootstrap-'.$name.'/v1','LEDGER_SCHEMA');return $h; }
    public static function validate(array $s): void {
        try {(new StateValidation($s))->run();} catch (\TypeError|\ValueError $e) {throw new \RuntimeException('O2_STATE_SHAPE',0,$e);}
    }
    public static function commandRef(mixed $ref):void {R::object($ref,['sequence_id','command_id','request_digest','result_digest']);R::id($ref['sequence_id']);R::id($ref['command_id']);R::digest($ref['request_digest']);R::digest($ref['result_digest']);}
    public static function command(array $s,array $ref): array {
        R::object($ref,['sequence_id','command_id','request_digest','result_digest']);
        $c=$s['commands'][self::key('command',[$s['trust']['instance_id'],$ref['sequence_id'],$ref['command_id']])]??null;
        R::require(is_array($c) && R::same($c['ref'],$ref),'COMMAND_ORIGINAL_MISSING');return $c;
    }
    public static function step(array $s,array $policy,string $id): array {
        $v=$s['steps'][self::key('step',[$policy['instance_id'],$policy['record_digest'],$id])]??null;
        R::require(is_array($v) && $v['completion']!==null,'STEP_NOT_READY');return $v;
    }
}
