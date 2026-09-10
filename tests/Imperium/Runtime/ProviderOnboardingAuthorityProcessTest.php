<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\OnboardingAuthorityFixture as F;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Admission,Resolver,Rules};
use PHPUnit\Framework\TestCase;

final class ProviderOnboardingAuthorityProcessTest extends TestCase
{
    private F $f;
    protected function setUp():void { $this->f=new F(); $this->f->enroll(); }
    protected function tearDown():void { $this->f->close(); }
    private function request(string $file,array $h,array $e,array $support):void {
        file_put_contents($this->f->root.'/'.$file,F::json(['envelope'=>F::json($e),'object'=>F::json($h),'support'=>array_map(F::json(...),$support)]));
    }
    private function start(string $mode,string $file):array {
        $command=[PHP_BINARY,__DIR__.'/Support/onboarding-authority-process.php',$this->f->root,$mode,$this->f->root.'/'.$file];
        $process=proc_open($command,[0=>['pipe','r'],1=>['file',$this->f->root.'/'.$file.'.out','w'],2=>['file',$this->f->root.'/'.$file.'.err','w']],$pipes,null,null,['bypass_shell'=>true]);
        self::assertIsResource($process); fclose($pipes[0]); return [$process,$file];
    }
    private function finish(array $child):array {
        [$process,$file]=$child; $deadline=microtime(true)+20;
        do { $status=proc_get_status($process); if (!$status['running']) { break; } usleep(10000); } while (microtime(true)<$deadline);
        if ($status['running']) { proc_terminate($process); proc_close($process); self::fail('Child timed out'); }
        $code=$status['exitcode']; proc_close($process);
        self::assertSame('',file_get_contents($this->f->root.'/'.$file.'.err'));
        return [$code,(string)file_get_contents($this->f->root.'/'.$file.'.out')];
    }
    public function testTwoRealAdmissionsAtOneHeadPublishOnlyOneCompleteFrame():void {
        $h=$this->f->policy(); $first=$this->f->sign($h,'AUTHORIZE_BOOTSTRAP_POLICY'); $second=$this->f->sign($h,'AUTHORIZE_BOOTSTRAP_POLICY');
        $this->request('first.json',$h,$first,array_values($this->f->sources)); $this->request('second.json',$h,$second,array_values($this->f->sources));
        $a=$this->start('admit','first.json'); $b=$this->start('admit','second.json'); file_put_contents($this->f->root.'/start','go');
        $out=[$this->finish($a),$this->finish($b)]; $codes=array_column($out,0); sort($codes); self::assertSame([0,1],$codes);
        self::assertStringContainsString('O2_STALE_HEAD',implode('',array_column($out,1)));
        $frame=$this->f->store->journal->read(); self::assertSame(2,$frame['generation']);
        self::assertCount(1,$frame['state']['onboarding']['acts']); self::assertCount(1,$frame['state']['onboarding']['admissions']);
        self::assertSame(F::json($h),F::json((new Resolver($this->f->store))->original(Rules::reference($h))));
    }
    public function testAbruptTerminationBeforeAndAfterPublicationAndRestartRecognition():void {
        $h=$this->f->policy(); $e=$this->f->sign($h,'AUTHORIZE_BOOTSTRAP_POLICY'); $sources=array_values($this->f->sources);
        $this->request('before.json',$h,$e,$sources); $this->request('after.json',$h,$e,$sources);
        [$code]=$this->finish($this->start('before-publication','before.json')); self::assertSame(55,$code);
        self::assertFileExists($this->f->root.'/before-ready'); self::assertSame(1,$this->f->head()['generation']);
        self::assertArrayNotHasKey('unpublished-test-mutation',$this->f->store->journal->read()['state']);
        file_put_contents($this->f->root.'/start','go'); [$code,$result]=$this->finish($this->start('after-publication','after.json')); self::assertSame(56,$code);
        self::assertSame('ORIGINAL_ADMITTED',json_decode($result,true,512,JSON_THROW_ON_ERROR)['status']); self::assertSame(2,$this->f->head()['generation']);
        $repeat=(new Admission($this->f->store))->retain(F::json($e),F::json($h),array_map(F::json(...),$sources));
        self::assertSame('HISTORICAL_RECOGNITION',$repeat['status']); self::assertSame(2,$this->f->head()['generation']);
    }
}
