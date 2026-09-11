<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\OnboardingLedgerFixture as F;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules as R;
use App\Imperium\Runtime\Onboarding\Ledger\{StateMigration,LedgerState};
use PHPUnit\Framework\TestCase;

final class ProviderOnboardingLedgerCorrectionTest extends TestCase
{
    private F $f;
    protected function setUp():void {$this->f=new F();$this->f->ready();}
    protected function tearDown():void {$this->f->close();}
    private function state():array {return $this->f->f->store->journal->read()['state'];}
    private function resumeRequest(array $ref,string $id='correction-resume'):array {$head=$this->f->f->head();$head['digest']='sha256:'.$head['digest'];return ['schema'=>'imperium.provider-onboarding-resume/v2','sequence_id'=>'sequence-test','command_id'=>$id,'expected_head'=>$head,'recognize_command_ref'=>$ref];}
    private function refuses(callable $action):void {$refused=false;try{$action();}catch(\RuntimeException|\InvalidArgumentException){$refused=true;}self::assertTrue($refused,'Malformed evidence/state must refuse');}
    private static function reseal(array $h):array {unset($h['record_digest']);$h['record_digest']=R::hash($h);return $h;}

    public function testRecoveredEnvelopeRequiresEveryOriginalAttributionField():void
    {
        $f=$this->f;$f->ports->fault='after-envelope';$this->refuses(fn()=>$f->advance('access',true));$s=$this->state()['onboarding'];$id=array_key_first($s['claims']);$claim=$s['claims'][$id];$path=$f->f->root.'/b1-envelope-'.substr($claim['record']['record_digest'],7).'.json';$original=json_decode(file_get_contents($path),true);$f->ports->fault='';$head=$f->f->head();
        $variants=[];
        foreach(array_keys($original) as $field){$v=$original;unset($v[$field]);$variants[]=$v;}
        $v=$original;$v['unexpected']=true;$variants[]=$v;
        foreach(array_keys($original['metadata']) as $field){$v=$original;unset($v['metadata'][$field]);$variants[]=$v;}
        foreach(['provider_response_id'=>'another-response','provenance'=>'another-adapter','operation_digest'=>'sha256:'.str_repeat('0',64),'response_digest'=>'sha256:'.str_repeat('1',64)] as $field=>$value){$v=$original;$v['metadata'][$field]=$value;$variants[]=$v;}
        $v=$original;$v['metadata']['extra']=true;$variants[]=$v;
        $v=$original;$v['claim_ref']['id']='another-claim';$variants[]=$v;
        $v=$original;$v['operation_digest']='sha256:'.str_repeat('2',64);$v['metadata']['operation_digest']=$v['operation_digest'];$variants[]=$v;
        $v=$original;$v['metadata']['usage']['calls']=1.0;$variants[]=$v;
        $v=$original;$v['metadata']['usage']['milliseconds']=10001;$variants[]=$v;
        $v=$original;$v['metadata']['usage']['unexpected']=0;$variants[]=$v;
        $v=$original;$v['response']='changed';$variants[]=$v;
        foreach($variants as $v){file_put_contents($path,json_encode($v,JSON_PRESERVE_ZERO_FRACTION));$this->refuses(fn()=>$f->custody->reconcile($id));self::assertSame($head,$f->f->head());$current=$this->state()['onboarding']['claims'][$id];self::assertNull($current['settled']);self::assertCount(4,$current['custody']);self::assertSame(['issue'=>1,'consume'=>1,'dispatch'=>1],$f->ports->counts);}
        file_put_contents($path,json_encode($original));$f->custody->reconcile($id);$current=$this->state()['onboarding']['claims'][$id];self::assertSame($original['metadata']['usage'],$current['settled']);self::assertCount(5,$current['custody']);foreach($current['custody'] as $checkpoint){self::assertSame(R::hash($current['operation']['prepared']),$checkpoint['body']['operation_digest']);}
        $head=$f->f->head();$f->custody->reconcile($id);self::assertSame($head,$f->f->head());self::assertSame(1,$f->ports->counts['dispatch']);
    }

    public function testResumeUsesCurrentAdvancingHeadAndItsOwnStableResult():void
    {
        $f=$this->f;$before=$f->last;$request=$this->resumeRequest($before);$result=$f->recovery->resume(json_encode($request));self::assertTrue(R::same($before,$result['sequence_head']));self::assertNotSame($result['sequence_head'],$result['result_ref']);
        $advance=$f->request('access');$advance['predecessor_ref']=$result['sequence_head'];$r=$f->custody->advance(json_encode($advance));$after=$r['result_ref'];$head=$f->f->head();$duplicate=$f->recovery->resume(json_encode($request,JSON_PRETTY_PRINT));self::assertTrue(R::same($after,$duplicate['sequence_head']));self::assertTrue(R::same($result['result_ref'],$duplicate['result_ref']));self::assertSame($head,$f->f->head());self::assertSame(1,$f->ports->counts['dispatch']);
        $f->f->now+=2000;$historical=$f->recovery->resume(json_encode($request));self::assertTrue(R::same($after,$historical['sequence_head']));self::assertSame($head,$f->f->head());
    }
    public function testPendingResumeNeverDeliversAndBadReferencesNeverPublish():void
    {
        $f=$this->f;$r=$f->advance('access');$request=$this->resumeRequest($r['result_ref']);$result=$f->recovery->resume(json_encode($request));self::assertSame('OUTCOME_UNKNOWN',$result['status']);self::assertTrue(R::same($r['result_ref'],$result['sequence_head']));self::assertSame(['issue'=>0,'consume'=>0,'dispatch'=>0],$f->ports->counts);
        $head=$f->f->head();foreach(['result_digest'=>'sha256:'.str_repeat('a',64),'sequence_id'=>'another-sequence','command_id'=>'missing-command'] as $field=>$value){$bad=$this->resumeRequest($r['result_ref'],'invalid-resume-'.$field);$bad['recognize_command_ref'][$field]=$value;$this->refuses(fn()=>$f->recovery->resume(json_encode($bad)));self::assertSame($head,$f->f->head());}
        $bad=$this->resumeRequest($result['result_ref'],'resume-of-resume');$this->refuses(fn()=>$f->recovery->resume(json_encode($bad)));self::assertSame($head,$f->f->head());
        $duplicate=$f->recovery->resume(json_encode($request));self::assertTrue(R::same($r['result_ref'],$duplicate['sequence_head']));self::assertSame($head,$f->f->head());self::assertSame(['issue'=>0,'consume'=>0,'dispatch'=>0],$f->ports->counts);
    }

    public function testClosedPopulatedStateShapesAndOriginalCrossLinks():void
    {
        $f=$this->f;$f->advance('access',true);$valid=$this->state();$f->f->store->state($valid);self::assertCount(1,$valid['onboarding']['claims']);$head=$f->f->head();$variants=[];$s=$valid['onboarding'];
        $v=$s;$v['unknown']=[];$variants[]=$v;
        foreach(StateMigration::MAPS as $map){$v=$s;unset($v[$map]);$variants[]=$v;if($s[$map]!==[]){$v=$s;$v[$map]=array_values($v[$map]);$variants[]=$v;}}
        foreach(['sequences','commands','steps','slots','claims','budget_bindings'] as $map){$k=array_key_first($s[$map]);$v=$s;$v[$map][$k]['unknown']=true;$variants[]=$v;foreach(array_keys($s[$map][$k]) as $field){$v=$s;unset($v[$map][$k][$field]);$variants[]=$v;}$v=$s;$v[$map]['sha256:'.str_repeat('9',64)]=$v[$map][$k];unset($v[$map][$k]);$variants[]=$v;}
        $paths=[['migration']];foreach(['sequences'=>'registration','steps'=>'consumption','claims'=>'record','budget_bindings'=>'record'] as $map=>$field){$paths[]=[$map,array_key_first($s[$map]),$field];}$ck=array_key_first($s['claims']);$paths[]=['claims',$ck,'operation','record'];foreach(array_keys($s['claims'][$ck]['custody']) as $i){$paths[]=['claims',$ck,'custody',$i];}
        foreach($paths as $path){foreach(['extra','missing','foreign'] as $kind){$v=$s;$h=&$v;foreach($path as $part){$h=&$h[$part];}if($kind==='extra'){$h['body']['unrecognized']=true;}elseif($kind==='missing'){unset($h['body'][array_key_first($h['body'])]);}else{$h['citadel_id']='foreign-citadel';}$h=self::reseal($h);unset($h);$variants[]=$v;}}
        $v=$s;$v['claims'][$ck]['operation']['unexpected']=true;$variants[]=$v;
        $v=$s;$v['claims'][$ck]['settled']['calls']=0;$variants[]=$v;
        $v=$s;$v['claims'][$ck]['custody'][4]['body']['response_envelope']['operation_digest']='sha256:'.str_repeat('0',64);$v['claims'][$ck]['custody'][4]=self::reseal($v['claims'][$ck]['custody'][4]);$variants[]=$v;
        $v=$s;$v['claims'][$ck]['record']['body']['lease_consumption']['consumed']=false;$v['claims'][$ck]['record']=self::reseal($v['claims'][$ck]['record']);$variants[]=$v;
        $v=$s;$v['group_inputs']['sha256:'.str_repeat('0',64)]=$s['migration'];$variants[]=$v;
        $v=$s;$v['attempt_outcomes']['sha256:'.str_repeat('0',64)]=[];$variants[]=$v;
        $v=$s;$v['bindings']=['unexpected'=>[]];$variants[]=$v;
        $v=$s;$v['commands']=array_fill(0,4097,[]);$variants[]=$v;
        foreach($variants as $v){$this->refuses(fn()=>$f->f->store->state([...$valid,'onboarding'=>$v]));self::assertSame($head,$f->f->head());}
        $f->f->store->state($valid);self::assertSame(1,$f->ports->counts['dispatch']);
    }

    public function testMigrationDigestsKeepFirstAdmissionHistoryAfterNewRevocation():void
    {
        $f=$this->f;$before=$this->state()['onboarding']['migration'];$f->f->revoke('policy',$f->policy['id']);$state=$this->state();self::assertSame($before,$state['onboarding']['migration']);$f->f->store->state($state);$head=$f->f->head();self::assertSame($before,(new StateMigration($f->f->store))->migrate($head));self::assertSame($head,$f->f->head());
        foreach(['prior_subtree_digest','preserved_maps_digest'] as $field){$v=$state;$v['onboarding']['migration']['body'][$field]='sha256:'.str_repeat('0',64);$v['onboarding']['migration']=self::reseal($v['onboarding']['migration']);$this->refuses(fn()=>$f->f->store->state($v));}
    }
    public function testMalformedV2BlocksMigrationReplayAndEveryOwningEntry():void
    {
        $f=$this->f;$f->f->store->journal->change(function(array &$state):void{$k=array_key_first($state['onboarding']['budget_bindings']);$h=$state['onboarding']['budget_bindings'][$k]['record'];$h['body']['unexpected']=true;$state['onboarding']['budget_bindings'][$k]['record']=self::reseal($h);});$head=$f->f->head();
        foreach([fn()=>(new StateMigration($f->f->store))->migrate($head),fn()=>$f->recovery->status('sequence-test'),fn()=>$f->advance('access'),fn()=>$f->f->revoke('policy',$f->policy['id'])] as $action){$this->refuses($action);self::assertSame($head,$f->f->head());}self::assertSame(['issue'=>0,'consume'=>0,'dispatch'=>0],$f->ports->counts);
    }
}
