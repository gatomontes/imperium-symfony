<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\OnboardingLedgerFixture as F;
use PHPUnit\Framework\{TestCase,Attributes\DataProvider};
final class ProviderOnboardingLedgerProcessTest extends TestCase
{
    private F $f;private array $workers=[];
    protected function setUp():void{$this->f=new F();$this->f->ready();}
    protected function tearDown():void{foreach($this->workers as $p){if(is_resource($p)){if(proc_get_status($p)['running']){proc_terminate($p);}proc_close($p);}}$this->f->close();}
    private function input():array{$q=$this->f->request('access');file_put_contents($this->f->f->root.'/b1-worker.json',json_encode(['now'=>$this->f->f->now,'citadel'=>$this->f->f->store->citadel,'operation'=>$this->f->ports->operation,'request'=>$q]));return $q;}
    private function start(string $token,string $mode):mixed{$root=$this->f->f->root;$p=proc_open([PHP_BINARY,'-d','allow_url_fopen=0','-d','disable_functions=curl_exec,curl_multi_exec,fsockopen,pfsockopen,stream_socket_client,socket_connect',__DIR__.'/Support/onboarding-ledger-worker.php',$root,$token,$mode],[0=>['pipe','r'],1=>['file',$root.'/b1-out-'.$token,'w'],2=>['file',$root.'/b1-err-'.$token,'w']],$pipes);self::assertIsResource($p);fclose($pipes[0]);$this->workers[]=$p;return $p;}
    private function release(array $tokens):void{$deadline=microtime(true)+40;foreach($tokens as $t){while(!is_file($this->f->f->root.'/b1-ready-'.$t)){if(microtime(true)>$deadline){self::fail('worker ready timeout');}usleep(10000);}}file_put_contents($this->f->f->root.'/b1-go','go');}
    private function finish(mixed $p):int{$deadline=microtime(true)+60;do{$s=proc_get_status($p);if(!$s['running']){return $s['exitcode'];}if(microtime(true)>$deadline){proc_terminate($p);self::fail('worker completion timeout');}usleep(10000);}while(true);}
    public function testTwoProcessesUseOneOriginalReservationAndDispatch():void{$q=$this->input();$a=$this->start('one','normal');$b=$this->start('two','normal');$this->release(['one','two']);$codes=[$this->finish($a),$this->finish($b)];sort($codes);self::assertSame([0,2],$codes);$counts=json_decode(file_get_contents($this->f->f->root.'/b1-counts.json'),true);self::assertSame(['issue'=>1,'consume'=>1,'dispatch'=>1],$counts);self::assertCount(1,$this->f->f->store->journal->read()['state']['onboarding']['claims']);}
    #[DataProvider('stages')]
    public function testAbruptExitLeavesOriginalEvidenceAndNoReissue(string $stage,int $checkpoints,array $counts,bool $recoverable):void{
        $q=$this->input();$p=$this->start('crash',$stage);$this->release(['crash']);self::assertSame(73,$this->finish($p));$s=$this->f->f->store->journal->read()['state']['onboarding'];
        if($stage==='before-reservation'){self::assertSame([],$s['claims']);return;}
        $id=array_key_first($s['claims']);$c=$s['claims'][$id];self::assertNull($c['settled']);self::assertCount($checkpoints,$c['custody']);
        $file=$this->f->f->root.'/b1-counts.json';self::assertSame($counts,is_file($file)?json_decode(file_get_contents($file),true):['issue'=>0,'consume'=>0,'dispatch'=>0]);
        self::assertSame('HISTORICAL_RECOGNITION',$this->f->custody->advance(json_encode($q))['status']);
        try{$this->f->custody->reconcile($id);self::assertTrue($recoverable);}catch(\RuntimeException){self::assertFalse($recoverable);}
        $c=$this->f->f->store->journal->read()['state']['onboarding']['claims'][$id];self::assertSame($recoverable,$c['settled']!==null);self::assertSame(['issue'=>0,'consume'=>0,'dispatch'=>0],$this->f->ports->counts);
    }
    public static function stages():iterable{
        yield ['before-reservation',0,[0,0,0],false];
        foreach([['after-reservation',0,0,0,0,false],['before-issue',1,0,0,0,false],['issue',1,1,0,0,false],['before-consume',2,1,0,0,false],['consume',2,1,1,0,false],['before-dispatch',3,1,1,0,false],['dispatch',3,1,1,1,false],['before-envelope',4,1,1,1,false],['after-envelope',4,1,1,1,true],['after-response',5,1,1,1,true]] as [$stage,$checkpoints,$i,$c,$d,$r]){yield [$stage,$checkpoints,['issue'=>$i,'consume'=>$c,'dispatch'=>$d],$r];}
    }

    #[DataProvider('revocationStages')]
    public function testCompetingRealRevocationStopsNextCheckpoint(string $stage,int $dispatches):void{
        $this->input();$p=$this->start('revocation','pause-'.$stage);$this->release(['revocation']);$deadline=microtime(true)+40;
        while(!is_file($this->f->f->root.'/b1-checkpoint-ready')){if(microtime(true)>$deadline){self::fail('checkpoint barrier timeout');}usleep(10000);}
        $this->f->f->revoke('policy',$this->f->policy['id']);file_put_contents($this->f->f->root.'/b1-checkpoint-go','go');self::assertSame(2,$this->finish($p));
        $counts=json_decode(file_get_contents($this->f->f->root.'/b1-counts.json'),true);self::assertSame($dispatches,$counts['dispatch']);$c=array_values($this->f->f->store->journal->read()['state']['onboarding']['claims'])[0];self::assertNull($c['settled']);
    }
    public static function revocationStages():iterable{yield ['issue',0];yield ['consume',0];yield ['dispatch',1];yield ['after-envelope',1];}
    public function testRevocationWinsBeforeReservation():void{$this->input();$p=$this->start('first','normal');$this->f->f->revoke('policy',$this->f->policy['id']);$this->release(['first']);self::assertSame(2,$this->finish($p));self::assertSame([],$this->f->f->store->journal->read()['state']['onboarding']['claims']);self::assertFileDoesNotExist($this->f->f->root.'/b1-counts.json');}
}
