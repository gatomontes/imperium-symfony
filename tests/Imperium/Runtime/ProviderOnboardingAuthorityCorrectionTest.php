<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Admission,Resolver,Rules};
use App\Tests\Imperium\Runtime\Support\OnboardingAuthorityFixture as F;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Real synthetic producers; no authority map insertion or private deployment access. */
final class ProviderOnboardingAuthorityCorrectionTest extends TestCase
{
    private F $f;
    protected function setUp():void { $this->f=new F(); $this->f->enroll(); }
    protected function tearDown():void { $this->f->close(); }
    private function refuses(callable $operation,string $code):void {
        $head=$this->f->head(); $caught=null;
        try { $operation(); } catch (\RuntimeException|\InvalidArgumentException $error) { $caught=$error; }
        self::assertNotNull($caught); self::assertStringContainsString($code,$caught->getMessage()); self::assertSame($head,$this->f->head());
    }
    #[DataProvider('boundaryTimes')]
    public function testSignedSlotHalfOpenAdmissionResolutionAndHistoricalRecognition(int $elapsed):void {
        $policy=$this->f->policy(); $policy['body']['effect_slots'][0]['authority_mode']='signed_act';
        $policy['body']['effect_slots'][0]['expires_at']=$this->f->now+2; unset($policy['record_digest']); $policy=Rules::seal($policy);
        $this->f->admitPolicy($policy); $ref=$policy['body']['effect_slots'][0]['terms_rule']['object_ref']; $terms=$this->f->sources[Rules::key($ref)];
        $e=$this->f->sign($terms,'ADMIT_BOOTSTRAP_EVIDENCE',Rules::reference($policy));
        $result=(new Admission($this->f->store))->retain(F::json($e),F::json($terms)); $this->f->now+=$elapsed;
        $resolve=fn()=>(new Resolver($this->f->store))->current(F::authority($result),'ADMIT_BOOTSTRAP_EVIDENCE',$ref);
        $fresh=$this->f->sign($terms,'ADMIT_BOOTSTRAP_EVIDENCE',Rules::reference($policy));
        $fresh['payload']['expires_at']=$policy['body']['expires_at']; $fresh=$this->f->resign($fresh);
        $admit=fn()=>(new Admission($this->f->store))->retain(F::json($fresh),F::json($terms));
        if ($elapsed<2) { self::assertFalse($resolve()['execution_authority']); self::assertSame('ORIGINAL_ADMITTED',$admit()['status']); }
        else { $this->refuses($resolve,'SIGNED_SLOT_CURRENT'); $this->refuses($admit,'SIGNED_SLOT_CURRENT'); }
        $head=$this->f->head(); $recognition=(new Admission($this->f->store))->retain(F::json($e),F::json($terms));
        self::assertSame('HISTORICAL_RECOGNITION',$recognition['status']); self::assertFalse($recognition['current_authority']); self::assertSame($head,$this->f->head());
    }
    #[DataProvider('boundaryTimes')]
    public function testPolicyOwnExpiryGovernsPolicyAndSupportingSourceWhileActLives(int $elapsed):void {
        $policy=$this->f->policy(); $policy['body']['expires_at']=$this->f->now+2;
        foreach ($policy['body']['effect_slots'] as &$slot) { $slot['expires_at']=$this->f->now+2; } unset($slot,$policy['record_digest']);
        $policy=Rules::seal($policy); [$policy,$e,$result]=$this->f->admitPolicy($policy); $sources=array_map(F::json(...),array_values($this->f->sources));
        self::assertGreaterThan($policy['body']['expires_at'],$e['payload']['expires_at']); $this->f->now+=$elapsed;
        $resolver=new Resolver($this->f->store);
        $calls=[fn()=>$resolver->current(F::authority($result),'AUTHORIZE_BOOTSTRAP_POLICY',Rules::reference($policy)),
            fn()=>$resolver->original(Rules::reference($policy)),fn()=>$resolver->original($policy['sources'][0])];
        foreach ($calls as $call) { if ($elapsed<2) { self::assertIsArray($call()); } else { $this->refuses($call,'POLICY_CURRENT'); } }
        $head=$this->f->head(); $historical=(new Admission($this->f->store))->retain(F::json($e),F::json($policy),$sources);
        self::assertSame('HISTORICAL_RECOGNITION',$historical['status']); self::assertFalse($historical['current_authority']); self::assertSame($head,$this->f->head());
    }
    public static function boundaryTimes():iterable { yield 'before'=>[1]; yield 'at'=>[2]; yield 'after'=>[3]; }
    public function testAnotherMatchingLiveSlotCannotSubstituteForAmbiguousSignedAuthority():void {
        $policy=$this->f->policy();
        // Two real assessment steps retain independent one-use identities. The act
        // has no slot selector, so sharing exact terms cannot select a live one.
        $indices=[]; foreach ($policy['body']['effect_slots'] as $i=>$slot) { if ($slot['effect']==='AUTHORIZE_BOOTSTRAP_ASSESSMENT') { $indices[]=$i; } }
        [$first,$second]=$indices; $ref=$policy['body']['effect_slots'][$first]['terms_rule']['object_ref'];
        foreach ([$first,$second] as $i) { $policy['body']['effect_slots'][$i]['authority_mode']='signed_act'; $policy['body']['effect_slots'][$i]['terms_rule']['object_ref']=$ref; }
        $policy['body']['effect_slots'][$first]['expires_at']=$this->f->now+1;
        unset($policy['record_digest']); $policy=Rules::seal($policy); $this->f->admitPolicy($policy); $terms=$this->f->sources[Rules::key($ref)];
        $this->f->now+=2; $e=$this->f->sign($terms,'AUTHORIZE_BOOTSTRAP_ASSESSMENT',Rules::reference($policy));
        $e['payload']['expires_at']=$policy['body']['expires_at']; $e=$this->f->resign($e);
        $this->refuses(fn()=>(new Admission($this->f->store))->retain(F::json($e),F::json($terms)),'AMBIGUOUS_SIGNED_SLOT');
    }
    public function testCurrentResuppliedSourcesKeepTheirFirstProvenance():void {
        [$a]=$this->f->admitPolicy(); $s=$this->f->store->journal->read()['state']['onboarding']; $b=$a; $b['id']='policy-correction-b'; unset($b['record_digest']); $b=Rules::seal($b);
        $e=$this->f->sign($b,'AUTHORIZE_BOOTSTRAP_POLICY');
        $result=(new Admission($this->f->store))->retain(F::json($e),F::json($b),array_map(F::json(...),array_values($this->f->sources)));
        self::assertSame('ORIGINAL_ADMITTED',$result['status']);
        self::assertSame($s['evidence'],$this->f->store->journal->read()['state']['onboarding']['evidence']);
        self::assertFalse((new Resolver($this->f->store))->current(F::authority($result),'AUTHORIZE_BOOTSTRAP_POLICY',Rules::reference($b))['execution_authority']);
    }
    #[DataProvider('orderings')]
    public function testCoordinatedRevocationAndAdmissionUseTheSameOwningLock(bool $revokeFirst):void {
        [$a,$aAct,$aResult]=$this->f->admitPolicy(); $raws=array_map(F::json(...),array_values($this->f->sources));
        $b=$a; $b['id']='policy-ordered-b'; unset($b['record_digest']); $b=Rules::seal($b); $bAct=$this->f->sign($b,'AUTHORIZE_BOOTSTRAP_POLICY');
        $revocation=$this->f->store->make('imperium.bootstrap-revocation/v1','revocation-ordered',[
            'target_kind'=>'act','target_id'=>$aAct['payload']['nonce'],'expected_head'=>$this->f->head()]);
        $revokeAct=$this->f->sign($revocation,'REVOKE_BOOTSTRAP');
        $children=['revoke'=>$this->startWorker('revoke',$revokeAct,$revocation,[]),'admit'=>$this->startWorker('admit',$bAct,$b,$raws)];
        $order=$revokeFirst?['revoke','admit']:['admit','revoke'];
        foreach ($order as $i=>$name) {
            file_put_contents($this->f->root.'/'.$name.'.go','go'); [$exit,$output]=$this->finishWorker($children[$name],$name);
            if ($i===0) { self::assertSame(0,$exit,$output); self::assertSame('ORIGINAL_ADMITTED',json_decode($output,true,512,JSON_THROW_ON_ERROR)['status']); }
            else { self::assertSame(1,$exit,$output); self::assertSame('O2_STALE_HEAD',$output); }
        }
        self::assertSame(3,$this->f->head()['generation']);
        if ($revokeFirst) {
            $fresh=$this->f->sign($b,'AUTHORIZE_BOOTSTRAP_POLICY');
            $this->refuses(fn()=>(new Admission($this->f->store))->retain(F::json($fresh),F::json($b),$raws),'ACT_REVOKED');
        } else {
            $this->f->revoke('act',$aAct['payload']['nonce']);
            $this->refuses(fn()=>(new Resolver($this->f->store))->original(Rules::reference($b)),'ACT_REVOKED');
            $head=$this->f->head(); self::assertSame('HISTORICAL_RECOGNITION',(new Admission($this->f->store))->retain(F::json($bAct),F::json($b),$raws)['status']); self::assertSame($head,$this->f->head());
        }
        self::assertSame('HISTORICAL_RECOGNITION',(new Admission($this->f->store))->retain(F::json($aAct),F::json($a),$raws)['status']);
        self::assertFalse($aResult['effect_completed']);
    }
    public static function orderings():iterable { yield 'revocation commits first'=>[true]; yield 'admission commits first'=>[false]; }
    private function startWorker(string $name,array $e,array $h,array $raws):mixed {
        file_put_contents($this->f->root.'/'.$name.'.json',F::json(['envelope'=>F::json($e),'object'=>F::json($h),'support'=>$raws]));
        $process=proc_open([PHP_BINARY,__DIR__.'/Support/onboarding-authority-correction-worker.php',$this->f->root,$name],
            [0=>['pipe','r'],1=>['file',$this->f->root.'/'.$name.'.out','w'],2=>['file',$this->f->root.'/'.$name.'.err','w']],$pipes,null,null,['bypass_shell'=>true]);
        self::assertIsResource($process); fclose($pipes[0]); $deadline=microtime(true)+10;
        while (!is_file($this->f->root.'/'.$name.'.ready')) { if (microtime(true)>$deadline) { proc_terminate($process); proc_close($process); self::fail('Worker startup timeout'); } usleep(1000); }
        return $process;
    }
    private function finishWorker(mixed $process,string $name):array {
        $deadline=microtime(true)+20;
        do { $status=proc_get_status($process); if (!$status['running']) { break; } usleep(10000); } while (microtime(true)<$deadline);
        if ($status['running']) { proc_terminate($process); proc_close($process); self::fail('Worker timeout'); }
        $exit=$status['exitcode']; proc_close($process); self::assertSame('',file_get_contents($this->f->root.'/'.$name.'.err'));
        return [$exit,(string)file_get_contents($this->f->root.'/'.$name.'.out')];
    }
}
