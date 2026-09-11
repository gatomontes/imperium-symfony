<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\OnboardingLedgerFixture as F;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules as R;
use PHPUnit\Framework\{TestCase,Attributes\DataProvider};
final class ProviderOnboardingCustodyTest extends TestCase
{
    private F $f;protected function setUp():void{$this->f=new F();$this->f->ready();}protected function tearDown():void{$this->f->close();}
    private function claim():array{return array_values($this->f->f->store->journal->read()['state']['onboarding']['claims'])[0];}
    public function testOneUseCustodySettlesAndRecognizes():void{$f=$this->f;$q=$f->request('access');$r=$f->custody->advance(json_encode($q));self::assertSame(['issue'=>1,'consume'=>1,'dispatch'=>1],$f->ports->counts);$claim=$this->claim();self::assertCount(5,$claim['custody']);self::assertSame(5,$claim['settled']['milliseconds']);self::assertNull($claim['record']['body']['response_ref']);$head=$f->f->head();$f->custody->advance(json_encode($q));self::assertSame($head,$f->f->head());self::assertSame(1,$f->ports->counts['dispatch']);self::assertSame([],$f->f->store->journal->read()['state']['onboarding']['source_fences']);}
    #[DataProvider('faults')]
    public function testFaultsPreserveMaximumAndEvidenceOnlyRecovery(string $stage,int $checkpoints,bool $canRecover):void{
        $f=$this->f;$f->ports->fault=$stage;$q=$f->request('access');try{$f->custody->advance(json_encode($q));self::fail();}catch(\RuntimeException $e){self::assertSame('O2_CUSTODY_REFUSED_OR_OUTCOME_UNKNOWN',$e->getMessage());self::assertNull($e->getPrevious());}
        $c=$this->claim();self::assertNull($c['settled']);self::assertCount($checkpoints,$c['custody']);$counts=$f->ports->counts;$f->ports->fault='';$h=$f->f->head();$h['digest']='sha256:'.$h['digest'];
        $resume=['schema'=>'imperium.provider-onboarding-resume/v2','sequence_id'=>'sequence-test','command_id'=>'resume-command','expected_head'=>$h,'recognize_command_ref'=>$c['record']['body']['command_ref']];
        $out=$f->recovery->resume(json_encode($resume));self::assertSame($counts,$f->ports->counts);if($canRecover){self::assertNotNull($this->claim()['settled']);}else{self::assertSame('OUTCOME_UNKNOWN',$out['status']);self::assertNull($this->claim()['settled']);}
        $head=$f->f->head();$f->recovery->resume(json_encode($resume));self::assertSame($head,$f->f->head());self::assertSame($counts,$f->ports->counts);
    }
    public static function faults():iterable{yield ['issue',1,false];yield ['consume',2,false];yield ['dispatch',3,false];yield ['before-envelope',4,false];yield ['after-envelope',4,true];}
    #[DataProvider('revocations')]
    public function testCurrentnessAtEachCheckpoint(string $stage,string $change,int $dispatches):void{$f=$this->f;$once=false;$f->ports->hook=function(string $at)use($stage,$change,$f,&$once):void{if($at!==$stage||$once){return;}$once=true;if($change==='expiry'){$f->f->now+=1800;}else{$f->f->revoke('policy',$f->policy['id']);}};try{$f->advance('access',true);self::fail();}catch(\RuntimeException $e){self::assertSame('O2_CUSTODY_REFUSED_OR_OUTCOME_UNKNOWN',$e->getMessage());}self::assertSame($dispatches,$f->ports->counts['dispatch']);self::assertNull($this->claim()['settled']);}
    public static function revocations():iterable{foreach(['expiry','revocation'] as $change){yield ['issue',$change,0];yield ['consume',$change,0];yield ['dispatch',$change,1];yield ['after-envelope',$change,1];}}
    public function testRepeatedCallbackCannotDispatchAgain():void{$f=$this->f;$f->ports->repeat=true;try{$f->advance('access',true);self::fail();}catch(\RuntimeException){}self::assertSame(1,$f->ports->counts['dispatch']);self::assertNull($this->claim()['settled']);$id=array_key_first($f->f->store->journal->read()['state']['onboarding']['claims']);$f->custody->reconcile($id);self::assertNotNull($this->claim()['settled']);self::assertSame(1,$f->ports->counts['dispatch']);}
    public function testFabricatedConsumeReturnCannotComplete():void{$f=$this->f;$f->ports->fakeConsume=true;try{$f->advance('access',true);self::fail();}catch(\RuntimeException){}self::assertSame(0,$f->ports->counts['dispatch']);self::assertNull($this->claim()['settled']);}
    public function testChangedWireBeforeDispatchRefuses():void{$f=$this->f;$f->ports->hook=function(string $at)use($f):void{if($at==='consume'){$f->ports->operation['wire']='tampered';}};try{$f->advance('access',true);self::fail();}catch(\RuntimeException){}self::assertSame(0,$f->ports->counts['dispatch']);}

    #[DataProvider('leaseBoundary')]
    public function testHalfOpenLeaseAtReservation(int $offset,bool $allowed):void{$f=$this->f;$f->f->now=$f->ports->operation['expires_at']+$offset;$head=$f->f->head();try{$f->advance('access',true);self::assertTrue($allowed);}catch(\RuntimeException){self::assertFalse($allowed);self::assertSame($head,$f->f->head());}self::assertSame($allowed?1:0,$f->ports->counts['dispatch']);}
    public static function leaseBoundary():iterable{yield [-1,true];yield [0,false];yield [1,false];}
}
