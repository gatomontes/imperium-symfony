<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\{ProtectedMissionFixture,ConsumerProcess};
use PHPUnit\Framework\TestCase;

final class MissionAmendmentProcessTest extends TestCase
{
    private function approved(ProtectedMissionFixture $f):string {
        $input=ProtectedMissionFixture::input();$input['mission']=$f->state['authorizations'][$f->id]['dossier']['mission_plan']['protected_mission'];
        $cid=$f->call('prepare',$input)['challenge_id'];$p=$f->call('export',['challenge_id'=>$cid]);
        $f->call('submit',['challenge_id'=>$cid,'signature'=>$f->sign($p)]);return $cid;
    }
    private function race(ProtectedMissionFixture $f,array $requests,?int $first):array {
        $workers=[];$gates=[];
        foreach ($requests as $i=>$request) {
            $gate=$f->root.'/gate-'.$i;$file=$f->root.'/request-'.$i.'.json';file_put_contents($file,json_encode($request,JSON_THROW_ON_ERROR));
            $workers[$i]=new ConsumerProcess([PHP_BINARY,__DIR__.'/Support/mission_amendment_barrier_worker.php',$f->root,$file,$gate],$f->root,'amendment-'.$i);
            $workers[$i]->start();$gates[$i]=$gate;
        }
        $deadline=microtime(true)+10;
        foreach ($gates as $gate) {while(!is_file($gate.'.ready')) {if(microtime(true)>$deadline)self::fail('barrier readiness timeout');usleep(1000);}self::assertTrue(is_file($gate.'.ready'));}
        self::assertNotSame(file_get_contents($gates[0].'.ready'),file_get_contents($gates[1].'.ready'));
        $out=[];
        if ($first!==null) {
            file_put_contents($gates[$first],'release');$out[$first]=$this->workerResult($workers[$first]);
            self::assertTrue($workers[1-$first]->isRunning());
            file_put_contents($gates[1-$first],'release');$out[1-$first]=$this->workerResult($workers[1-$first]);
        } else {
            foreach($gates as $gate)file_put_contents($gate,'release');
            foreach($workers as $i=>$worker)$out[$i]=$this->workerResult($worker);
        }
        ksort($out);return $out;
    }
    private function workerResult(ConsumerProcess $worker):array {
        self::assertSame(0,$worker->wait());self::assertSame('',$worker->getErrorOutput());return json_decode($worker->getOutput(),true,512,JSON_THROW_ON_ERROR);
    }
    public function testForcedInspectionBeforeAndAfterActivationPreservesOnlyThePublishedHistory():void {
        foreach ([0,1] as $first) {
            $f=new ProtectedMissionFixture();$caps=$f->call('issue',['authorization_id'=>$f->id])['capabilities'];$f->call('consume',['capability'=>$caps[0]]);
            $cid=$this->approved($f);
            $out=$this->race($f,[['operation'=>'consume','arguments'=>['capability'=>$caps[1]]],['operation'=>'derive','arguments'=>['challenge_id'=>$cid]]],$first);
            self::assertTrue($out[1]['ok']);self::assertSame($first===0,$out[0]['ok']);
            self::assertSame($first===0?null:'PMA_AUTHORITY_INACTIVE',$out[0]['error'] ?? null);
            $a=$f->call('status',['authorization_id'=>$f->id]);$b=$f->call('status',['authorization_id'=>$out[1]['result']['authorization_id']]);
            self::assertSame($first===0?'INSPECTING':'ADMITTED',$a['lifecycle']['state']);self::assertSame('AUTHORIZED',$b['lifecycle']['state']);self::assertNull($b['receipt']);
            self::assertCount($first===0?2:1,$a['lifecycle']['history']);self::assertCount(0,$b['lifecycle']['history']);
        }
    }
    public function testTwoConcurrentApprovedReplacementsHaveExactlyOneWinner():void {
        $f=new ProtectedMissionFixture();$cids=[$this->approved($f),$this->approved($f)];
        $out=$this->race($f,array_map(fn($cid)=>['operation'=>'derive','arguments'=>['challenge_id'=>$cid]],$cids),null);
        $winners=array_values(array_filter($out,fn($r)=>$r['ok']));$losers=array_values(array_filter($out,fn($r)=>!$r['ok']));
        self::assertCount(1,$winners);self::assertCount(1,$losers);self::assertSame('PMA_STALE_PREDECESSOR',$losers[0]['error']);
        self::assertSame('CURRENT',$f->call('status',['authorization_id'=>$winners[0]['result']['authorization_id']])['currentness']);
    }
    public function testForcedRevocationVersusActivationAndLostResponseRestart():void {
        foreach ([0,1] as $first) {
            $f=new ProtectedMissionFixture();$cid=$this->approved($f);
            $out=$this->race($f,[['operation'=>'control','arguments'=>$f->control('revoke')],['operation'=>'derive','arguments'=>['challenge_id'=>$cid]]],$first);
            self::assertTrue($out[0]['ok']);self::assertSame($first===1,$out[1]['ok']);
            if ($first===0) self::assertSame('PMA_AUTHORITY_INACTIVE',$out[1]['error']);
            else self::assertSame('CURRENT',$f->call('status',['authorization_id'=>$out[1]['result']['authorization_id']])['currentness']);
        }
        $f=new ProtectedMissionFixture();$cid=$this->approved($f);$request=['operation'=>'derive','arguments'=>['challenge_id'=>$cid]];
        $worker=new ConsumerProcess([PHP_BINARY,__DIR__.'/Support/protected_mission_crash_worker.php',$f->root,'after-derive',base64_encode(json_encode($request))],$f->root,'lost-derive');
        self::assertSame(23,$worker->run());self::assertSame('',$worker->getOutput());self::assertSame('',$worker->getErrorOutput());
        $status=$f->call('challenge-status',['challenge_id'=>$cid]);self::assertSame('DERIVED',$status['status']);
        $before=hash_file('sha256',$f->root.'/authority.journal');
        try{$f->call('derive',['challenge_id'=>$cid]);self::fail('lost-response replay accepted');}catch(\RuntimeException $e){self::assertSame('PMA_CHALLENGE_NOT_APPROVED',$e->getMessage());}
        self::assertSame($before,hash_file('sha256',$f->root.'/authority.journal'));self::assertSame('AUTHORIZED',$f->call('status',['authorization_id'=>$status['authorization_id']])['lifecycle']['state']);
    }
    public function testTerminalCompletionWinsAgainstPreviouslyApprovedSameIdAmendment():void {
        $f=new ProtectedMissionFixture();$cid=$this->approved($f);$caps=$f->call('issue',['authorization_id'=>$f->id])['capabilities'];
        foreach(array_slice($caps,0,2) as $cap)$f->call('consume',['capability'=>$cap]);
        $out=$this->race($f,[['operation'=>'consume','arguments'=>['capability'=>$caps[2]]],['operation'=>'derive','arguments'=>['challenge_id'=>$cid]]],0);
        self::assertTrue($out[0]['ok']);self::assertFalse($out[1]['ok']);self::assertSame('PMA_TERMINAL',$out[1]['error']);
        $a=$f->call('status',['authorization_id'=>$f->id]);self::assertSame('COMPLETED',$a['lifecycle']['state']);self::assertSame($f->id,$a['receipt']['authorization_id']);
    }
}
