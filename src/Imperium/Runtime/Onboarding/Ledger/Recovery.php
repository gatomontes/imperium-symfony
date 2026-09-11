<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Ledger;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules as R,StrictJson};
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class Recovery
{
    public function __construct(private CommandLedger $ledger,private CustodyCoordinator $custody){}
    public function status(string $sequence):array {
        R::id($sequence);return $this->ledger->store->journal->inspect(function(array $f)use($sequence):array{
            $s=$this->ledger->store->state($f['state']);$seq=$s['sequences'][LedgerState::key('sequence',[$this->ledger->store->instance,$sequence])]??null;R::require(is_array($seq),'SEQUENCE_MISSING');
            $c=LedgerState::command($s,$seq['head']);$out=CommandLedger::presentation($c,['generation'=>$f['generation'],'digest'=>$f['record_digest']],true);$out['command_id']=null;$out['result_ref']=null;$out['mode']='status';$out['status']='RETAINED';$out['facts']=['pending_claims'=>count(array_filter($s['claims'],static fn(array $c):bool=>$c['settled']===null && $c['record']['body']['sequence_ref']['sequence_id']===$sequence))];return $out;
        });
    }
    public static function request(string $raw):array {
        R::require(strlen($raw)<=1048576,'REQUEST_LIMIT');$q=StrictJson::decode($raw);R::object($q,['schema','sequence_id','command_id','expected_head','recognize_command_ref']);R::require($q['schema']==='imperium.provider-onboarding-resume/v2','REQUEST_SCHEMA');R::id($q['sequence_id']);R::id($q['command_id']);CommandLedger::head($q['expected_head']);LedgerState::commandRef($q['recognize_command_ref']);return $q;
    }
    public function resume(string $raw):array {
        $q=self::request($raw);
        // Recognition record commits once; reconciliation is a separately linked evidence operation.
        $out=$this->ledger->store->journal->changeAtHead(function(array &$state,array $head)use($q,$raw):array{
            $store=$this->ledger->store;$s=$store->state($state);$key=LedgerState::key('command',[$store->instance,$q['sequence_id'],$q['command_id']]);$seq=$s['sequences'][LedgerState::key('sequence',[$store->instance,$q['sequence_id']])]??null;R::require(is_array($seq),'SEQUENCE_MISSING');
            if(isset($s['commands'][$key])){R::require(R::same($s['commands'][$key]['request'],$q),'COMMAND_CONFLICT');return ['result'=>self::presentation($s['commands'][$key],$head,true,$seq['head']),'claims'=>[]];}
            R::require(R::same(CommandLedger::head($q['expected_head']),$head),'STALE_HEAD');$original=LedgerState::command($s,$q['recognize_command_ref']);R::require($original['ref']['sequence_id']===$q['sequence_id'] && $original['request']['schema']==='imperium.provider-onboarding-request/v2','RESUME_SOURCE');R::require(count($s['commands'])<4096,'LIMIT_EXCEEDED');
            $result=$original['result'];$result['command_id']=$q['command_id'];$result['request_digest']=R::hash($q);$result['observed_head']=$head;$result['admission_status']='EVIDENCE_RECOGNITION';
            $c=['key'=>[$store->instance,$q['sequence_id'],$q['command_id']],'request'=>$q,'raw_digest'=>'sha256:'.hash('sha256',$raw),'result'=>$result,'ref'=>['sequence_id'=>$q['sequence_id'],'command_id'=>$q['command_id'],'request_digest'=>R::hash($q),'result_digest'=>R::hash($result)]];
            $state['onboarding']['commands'][$key]=$c;$ids=[];foreach($s['claims'] as $id=>$claim){if(R::same($claim['record']['body']['command_ref'],$original['ref'])){$ids[]=$id;}}
            LedgerState::validate($state['onboarding']);return ['result'=>self::presentation($c,$head,false,$seq['head']),'claims'=>$ids];
        });
        foreach($out['claims'] as $id){try{$this->custody->reconcile($id);}catch(\Throwable){$out['result']['status']='OUTCOME_UNKNOWN';$out['result']['reason_codes']=['OUTCOME_UNKNOWN'];}}
        $out['result']['mode']='resume';$out['result']['effects']['new_effects_this_command']=false;return $out['result'];
    }
    private static function presentation(array $c,array $head,bool $historical,array $sequenceHead):array {$out=CommandLedger::presentation($c,$head,$historical);$out['sequence_head']=$sequenceHead;return $out;}

}
