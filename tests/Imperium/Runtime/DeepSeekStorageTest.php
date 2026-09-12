<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\{OnboardingAuthorityFixture,DeepSeekFixture};
use App\Imperium\Runtime\Onboarding\DeepSeek\{EnvelopeStore,Runtime,Wire};
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules as R;
use Symfony\Component\HttpClient\MockHttpClient;
use PHPUnit\Framework\{TestCase,Attributes\DataProvider};

final class DeepSeekStorageTest extends TestCase
{
    private OnboardingAuthorityFixture $f;
    private EnvelopeStore $store;
    private array $workers=[];
    protected function setUp():void { $this->f=new OnboardingAuthorityFixture(); mkdir($this->f->root.'/responses'); $this->store=new EnvelopeStore($this->f->root.'/responses'); }
    protected function tearDown():void { foreach($this->workers as $p){if(is_resource($p)){if(proc_get_status($p)['running']){proc_terminate($p);}proc_close($p);}} $this->f->close(); }
    private function envelope():array {
        // Storage component input only, deliberately not admitted/executable authority.
        $claim=['schema'=>'imperium.bootstrap-cognition-claim/v1','id'=>'claim-storage-test','digest'=>'sha256:'.str_repeat('a',64)];
        $response=DeepSeekFixture::listing(); $digest='sha256:'.str_repeat('b',64);
        return ['claim_ref'=>$claim,'operation_digest'=>$digest,'response'=>$response,'metadata'=>[
            'provider_response_id'=>'local:deepseek-model-list:sha256:'.hash('sha256',$response),'operation_digest'=>$digest,
            'response_digest'=>R::hash($response),'usage'=>['calls'=>1,'input_tokens'=>0,'output_tokens'=>0,'cost_microusd'=>0,'milliseconds'=>4],'provenance'=>Wire::ADAPTER]];
    }
    public function testImmutableOriginalAndExactClaimPaths():void {
        $e=$this->envelope(); $this->store->retain($e); $this->store->retain($e); self::assertTrue(R::same($e,$this->store->read($e['claim_ref'])));
        $other=$e['claim_ref']; $other['id']='claim-other-test';
        try{$this->store->read($other);self::fail();}catch(\RuntimeException $x){self::assertSame('O2_ENVELOPE_MISSING',$x->getMessage());}
        $e['response']=' '.$e['response'];$e['metadata']['response_digest']=R::hash($e['response']);
        $this->expectExceptionMessage('O2_ENVELOPE_CONFLICT'); $this->store->retain($e);
    }
    public function testTraversalAndSymlinkRootsRefuse():void {
        $e=$this->envelope();$e['claim_ref']['id']='../../outside';
        try{$this->store->retain($e);self::fail();}catch(\RuntimeException $x){self::assertSame('O2_ID',$x->getMessage());}
        $link=$this->f->root.'/linked';$target=$this->f->root.'/responses';
        if(PHP_OS_FAMILY==='Windows') {
            $p=proc_open(['powershell','-NoProfile','-NonInteractive','-Command','New-Item','-ItemType','Junction','-Path',$link,'-Target',$target],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
            self::assertIsResource($p);fclose($pipes[0]);$output=stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);self::assertSame(0,proc_close($p),$output);
        } else { self::assertTrue(symlink($target,$link)); }
        self::assertIsString(readlink($link));
        $this->expectExceptionMessage('O2_ENVELOPE_SYMLINK');
        try { new EnvelopeStore($link); } finally { if(PHP_OS_FAMILY==='Windows'){rmdir($link);}else{unlink($link);} }
    }
    private function start(string $token,string $mode):mixed {
        $root=$this->f->root;
        $p=proc_open([PHP_BINARY,'-d','allow_url_fopen=0',__DIR__.'/Support/deepseek-storage-worker.php',$root,$token,$mode],
            [0=>['pipe','r'],1=>['file',$root.'/out-'.$token,'w'],2=>['file',$root.'/err-'.$token,'w']],$pipes);
        self::assertIsResource($p);fclose($pipes[0]);$this->workers[]=$p;return $p;
    }
    private function release(array $tokens):void {
        $deadline=microtime(true)+20;
        foreach($tokens as $token){while(!is_file($this->f->root.'/ready-'.$token)){if(microtime(true)>$deadline){self::fail('Worker ready timeout');}usleep(10000);}}
        file_put_contents($this->f->root.'/go','go');
    }
    private function finish(mixed $p):int {
        $deadline=microtime(true)+20;
        do{$s=proc_get_status($p);if(!$s['running']){return $s['exitcode'];}if(microtime(true)>$deadline){self::fail('Worker exit timeout');}usleep(10000);}while(true);
    }
    public function testConcurrentRetentionPublishesOneCompleteOriginal():void {
        $e=$this->envelope();file_put_contents($this->f->root.'/input.json',json_encode($e));
        $a=$this->start('a','normal');$b=$this->start('b','normal');$this->release(['a','b']);
        self::assertSame(0,$this->finish($a));self::assertSame(0,$this->finish($b));
        self::assertTrue(R::same($e,$this->store->read($e['claim_ref'])));self::assertCount(1,glob($this->f->root.'/responses/*.json'));
    }
    public function testConcurrentConflictingRetentionCannotOverwrite():void {
        $e=$this->envelope();file_put_contents($this->f->root.'/input.json',json_encode($e));
        $a=$this->start('a','normal');$b=$this->start('b','conflict');$this->release(['a','b']);
        $codes=[$this->finish($a),$this->finish($b)];sort($codes);self::assertSame([0,2],$codes);
        $retained=$this->store->read($e['claim_ref']);self::assertContains($retained['response'],[$e['response'],' '.$e['response']]);
        self::assertCount(1,glob($this->f->root.'/responses/*.json'));
    }
    #[DataProvider('publicationStages')]
    public function testProcessLossAtAtomicPublication(string $stage,bool $retained):void {
        $e=$this->envelope();file_put_contents($this->f->root.'/input.json',json_encode($e));
        $p=$this->start('crash',$stage);$this->release(['crash']);self::assertSame(73,$this->finish($p));
        if($retained){self::assertTrue(R::same($e,$this->store->read($e['claim_ref'])));}
        else{$this->expectExceptionMessage('O2_ENVELOPE_MISSING');$this->store->read($e['claim_ref']);}
    }
    public static function publicationStages():iterable {yield ['before-publish',false];yield ['after-publish',true];}
    #[DataProvider('publicationStages')]
    public function testO2MetadataAndEvidenceOnlyRestart(string $stage,bool $recoverable):void {
        $f=new DeepSeekFixture(storageHook:static function(string $at)use($stage):void {if($stage===$at){throw new \RuntimeException('Synthetic interruption');}});
        try{
            $f->ready();try{$f->advance('access');self::fail();}catch(\RuntimeException $e){self::assertSame('O2_CUSTODY_REFUSED_OR_OUTCOME_UNKNOWN',$e->getMessage());}
            $c=$f->claim();self::assertNull($c['settled']);self::assertCount(4,$c['custody']);self::assertCount(1,$f->requests);
            $restart=new Runtime($f->f->store,$f->adapter,$f->keys,new EnvelopeStore($f->f->root.'/responses'),new MockHttpClient(static function(){throw new \RuntimeException('No recovery dispatch');}));
            $id=array_key_first($f->f->store->journal->read()['state']['onboarding']['claims']);
            try{$restart->reconcile($id);self::assertTrue($recoverable);}catch(\RuntimeException){self::assertFalse($recoverable);}
            self::assertSame($recoverable,$f->claim()['settled']!==null);self::assertCount(1,$f->requests);
        }finally{$f->close();}
    }
}
