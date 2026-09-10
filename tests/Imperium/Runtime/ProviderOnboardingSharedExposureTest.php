<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\{OnboardingLedgerFixture as F,CitadelFormationFixture,FormationCustodyFixture};
use App\Imperium\Runtime\Citadel\Formation\{SharedExposure,SessionExposure};
use PHPUnit\Framework\TestCase;
final class ProviderOnboardingSharedExposureTest extends TestCase
{
    private CitadelFormationFixture $fc;private FormationCustodyFixture $custody;private F $f;private string $sid;private int $budgetCalls=13;
    protected function setUp():void{$this->fc=$fc=new CitadelFormationFixture();$fc->appoint();$id=$fc->receive()['intake_id'];$terms=$fc->terms($id,'interview');$terms['transport']=FormationCustodyFixture::authorization();$terms['per_call']['output_tokens']=4096;$this->sid=$fc->grant($id,'interview',$terms);$this->custody=new FormationCustodyFixture($fc->root,$fc->clock);$c=$this->custody;$wire=new SmallSharedFormationWire($c->wire);
        $c->broker=new \App\Imperium\Runtime\Clavium\FormationClaimCustodyBroker($fc->journal,$c->container->get(\App\Imperium\Runtime\Citadel\Formation\FormationSessionAuthority::class),$c->container->get(\App\Imperium\Runtime\Clavium\FormationSessionLeaseService::class),$c->credentials,$wire,$c->container->get(\App\Imperium\Runtime\Clavium\ProviderResponseEnvelopeService::class),$fc->clock);
        $c->transport=new \App\Imperium\Runtime\Citadel\Formation\CustodiedFormationTransport($wire,$c->broker);
        $c->cognition=new \App\Imperium\Runtime\Citadel\Formation\FormationCognition($fc->journal,$fc->signatures,$fc->personnel,$c->transport,$c->container->get(\App\Imperium\Runtime\Clavium\ProviderResponseEnvelopeService::class),$c->container->get(\App\Imperium\Runtime\Clavium\FormationSessionLeaseService::class),$fc->clock,new \App\Imperium\Runtime\Curia\ReceivingFormationHandoffService($fc->root,$fc->journal));
        $this->f=new F($fc,[$this->sid],budgetLimits:['calls'=>$this->budgetCalls]);$this->f->ready();}
    protected function tearDown():void{$this->f->close();$this->fc->close();}
    public function testOnboardingReservationFencesActualFormationPath():void{$f=$this->f;$f->advance('access');try{$this->custody->cognition->call($this->sid,'formation-shared-0001');self::fail();}catch(\RuntimeException $e){self::assertStringContainsString('OUTCOME_UNKNOWN',$e->getMessage());}self::assertSame([],$this->fc->journal->read()['state']['sessions'][$this->sid]['attempts']);self::assertSame(['issue'=>0,'consume'=>0,'dispatch'=>0],$this->custody->counts());}
    public function testFormationReservationFencesOnboardingAndOwnCustodyStillWorks():void{$call=$this->custody->pending($this->fc,$this->sid);$head=$this->f->f->head();try{$this->f->advance('access');self::fail();}catch(\RuntimeException $e){self::assertStringContainsString('SHARED_CONCURRENCY',$e->getMessage());}self::assertSame($head,$this->f->f->head());$this->custody->broker->invoke(...$call);self::assertSame(['issue'=>1,'consume'=>1,'dispatch'=>1],$this->custody->counts());}
    public function testSettledOnboardingExposureAllowsOriginalFormationLimits():void{$this->f->advance('access',true);$this->custody->pending($this->fc,$this->sid);self::assertCount(1,$this->fc->journal->read()['state']['sessions'][$this->sid]['attempts']);$claim=array_values($this->fc->journal->read()['state']['onboarding']['claims'])[0];self::assertSame(1,$claim['settled']['calls']);}
    public function testUnmappedNewFormationSessionCannotEscapeV2():void{$id=$this->fc->receive('Another bounded public request','synthetic-request-0002')['intake_id'];$sid=$this->fc->grant($id,'interview',$this->fc->terms($id,'interview'));try{$this->fc->cognition->call($sid,'unmapped-attempt-0001');self::fail();}catch(\RuntimeException $e){self::assertStringContainsString('SHARED_BUDGET_SOURCE_UNRESOLVED',$e->getMessage());}self::assertSame([],$this->fc->journal->read()['state']['sessions'][$sid]['attempts']);}
    public function testZeroFeeAccessDoesNotRelaxPositiveFormationMeter():void{$m=['calls'=>1,'input_tokens'=>0,'output_tokens'=>0,'cost_microusd'=>0,'milliseconds'=>10000];SharedExposure::meters($m);$this->expectExceptionMessage('CMF030_LIMITS_INVALID');SessionExposure::validate($m);}
    public function testSettledUsageIsSubtractedFromTheSharedExactMeter():void{
        $this->f->advance('access',true);$state=$this->fc->journal->read()['state'];$binding=array_values($state['onboarding']['budget_bindings'])[0]['record']['body'];
        $root=$state['onboarding']['evidence'][\App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules::key($binding['limit_ref'])]['record'];$limits=json_decode($root['body']['content'],true)['limits'];
        $maximum=['calls'=>1,'input_tokens'=>0,'output_tokens'=>0,'cost_microusd'=>0,'milliseconds'=>$limits['milliseconds']];
        $this->expectExceptionMessage('O2_SHARED_BUDGET_EXHAUSTED');SharedExposure::check($state,$binding['budget_identity'],$limits,$maximum);
    }

    public function testRevokedAssociationOriginalStopsActualFormationReservation():void{
        $h=$this->f->f->store->make('imperium.bootstrap-revocation/v1','revocation-association',['target_kind'=>'policy','target_id'=>$this->f->policy['id'],'expected_head'=>$this->f->f->head()]);
        (new \App\Imperium\Runtime\Onboarding\AuthorityAdmission\Admission($this->f->f->store))->retain($this->f->f::json($this->f->sign($h,'REVOKE_BOOTSTRAP')),$this->f->f::json($h));
        $head=$this->f->f->head();try{$this->custody->cognition->call($this->sid,'revoked-association-attempt');self::fail();}catch(\RuntimeException $e){self::assertStringContainsString('REVOKED',$e->getMessage());}
        self::assertSame($head,$this->f->f->head());self::assertSame(['issue'=>0,'consume'=>0,'dispatch'=>0],$this->custody->counts());
    }

    public function testSimultaneousRealFCAndOnboardingReservationCannotOverspend():void{
        $this->f->close();$this->fc->close();$this->budgetCalls=1;$this->setUp();$root=$this->fc->root;
        file_put_contents($root.'/b1-cross-input.json',json_encode(['now'=>$this->fc->clock->at,'citadel'=>$this->f->f->store->citadel,'operation'=>$this->f->ports->operation,'request'=>$this->f->request('access'),'session_id'=>$this->sid]));$workers=[];
        try{
            foreach(['formation','onboarding'] as $kind){$p=proc_open([PHP_BINARY,'-d','allow_url_fopen=0','-d','disable_functions=curl_exec,curl_multi_exec,fsockopen,pfsockopen,stream_socket_client,socket_connect',__DIR__.'/Support/onboarding-shared-worker.php',$root,$kind],[0=>['pipe','r'],1=>['file',$root.'/cross-out-'.$kind,'w'],2=>['file',$root.'/cross-err-'.$kind,'w']],$pipes);self::assertIsResource($p);fclose($pipes[0]);$workers[]=$p;}
            $deadline=microtime(true)+40;foreach(['formation','onboarding'] as $kind){while(!is_file($root.'/cross-ready-'.$kind)){if(microtime(true)>$deadline){self::fail('cross ready timeout');}usleep(10000);}}
            file_put_contents($root.'/cross-go','go');$codes=[];foreach($workers as $p){$deadline=microtime(true)+60;do{$status=proc_get_status($p);if(!$status['running']){$codes[]=$status['exitcode'];break;}if(microtime(true)>$deadline){self::fail('cross finish timeout');}usleep(10000);}while(true);}
            sort($codes);self::assertSame([0,2],$codes);$s=$this->fc->journal->read()['state'];self::assertSame(1,count($s['onboarding']['claims'])+count($s['sessions'][$this->sid]['attempts']));
            $b1=is_file($root.'/b1-counts.json')?json_decode(file_get_contents($root.'/b1-counts.json'),true)['dispatch']:0;self::assertSame(1,$b1+$this->custody->counts()['dispatch']);
        }finally{foreach($workers as $p){if(is_resource($p)){if(proc_get_status($p)['running']){proc_terminate($p);}proc_close($p);}}}
    }

}

/** Fixed synthetic bounds fit the shared signed budget; the original fixture is unchanged. */
final readonly class SmallSharedFormationWire implements \App\Imperium\Runtime\Citadel\Formation\FormationWireAdapter
{
    public function __construct(private \App\Tests\Imperium\Runtime\Support\RecordingFormationWire $inner){}
    public function prepare(array $request,array $terms):array{$op=$this->inner->prepare($request,$terms);$op['maximum']['output_tokens']=4096;$op['maximum']['cost_microusd']=$op['maximum']['input_tokens']+4096;return $op;}
    public function dispatch(array $operation,mixed $authentication):array{return $this->inner->dispatch($operation,$authentication);}
}
