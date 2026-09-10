<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\{OnboardingLedgerFixture as F,OnboardingAuthorityFixture};
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules as R,Resolver,Admission};
use App\Imperium\Runtime\Onboarding\Ledger\{StateMigration,CommandLedger,LedgerState};
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
final class ProviderOnboardingLedgerTest extends TestCase
{
    private F $f;protected function setUp():void{$this->f=new F();}protected function tearDown():void{$this->f->close();}
    private function refuses(callable $call,string $code):void{$head=$this->f->f->head();try{$call();self::fail('Expected '.$code);}catch(\RuntimeException $e){self::assertStringContainsString($code,$e->getMessage());}self::assertSame($head,$this->f->f->head());}
    public function testMigrationIsExplicitIdempotentAndPreservesB0():void{
        $f=$this->f;$head=$f->f->head();$s=$f->f->store->journal->read()['state']['onboarding'];$m=(new StateMigration($f->f->store))->migrate($head);self::assertSame($s['migration'],$m);self::assertSame($head,$f->f->head());
        $r=(new Admission($f->f->store))->retain($f->f::json($f->envelope),$f->f::json($f->policy),array_map($f->f::json(...),array_values($f->f->sources)));self::assertSame('HISTORICAL_RECOGNITION',$r['status']);self::assertSame($head,$f->f->head());
        $f->f->revoke('policy',$f->policy['id']);self::assertSame($s['migration'],$f->f->store->journal->read()['state']['onboarding']['migration']);$this->refuses(fn()=>$f->advance(),'REVOKED');
    }
    public function testRealPolicyRegistrationConfigureAndPCompletion():void{
        $f=$this->f;$r=$f->advance();$f->advance('configure');$s=$f->f->store->journal->read()['state']['onboarding'];self::assertCount(1,$s['steps']);self::assertSame([],$s['slots']);
        $slot=$f->policy['body']['effect_slots'][0];$authority=['kind'=>'policy_effect','policy_ref'=>R::reference($f->policy),'policy_admission_ref'=>R::reference($f->admission['admission']),'slot_id'=>$slot['slot_id'],'slot_digest'=>R::hash($slot),'terms_ref'=>$slot['terms_rule']['object_ref'],'derivation_input_refs'=>[]];
        $this->refuses(fn()=>(new Resolver($f->f->store))->current($authority,$slot['effect'],$slot['terms_rule']['object_ref']),'DYNAMIC_PREREQUISITES_MISSING');
        $f->advance('admit-evidence');$s=$f->f->store->journal->read()['state']['onboarding'];self::assertCount(2,$s['steps']);self::assertCount(1,$s['slots']);self::assertSame([],$s['claims']);
        foreach($s['steps'] as $step){self::assertNotNull($step['completion']);}self::assertFalse($r['operational_flags']['execution_authority']);
    }
    public function testMissingDependencyCannotUseRetainedB0Receipt():void{$f=$this->f;$f->advance();$this->refuses(fn()=>$f->advance('admit-evidence'),'STEP_NOT_READY');}
    public function testSignedNonceAndStepAreConsumedOnce():void{$this->f->close();$this->f=$f=new F(signed:true);$f->ready();$s=$f->f->store->journal->read()['state']['onboarding'];self::assertCount(1,$s['slots']);self::assertMatchesRegularExpression('/^[a-f0-9]{48}$/',array_values($s['slots'])[0]['authority_key'][2]);$this->refuses(fn()=>$f->advance('admit-evidence'),'STEP_ALREADY_CONSUMED');}
    public function testSemanticReplayBeforeExpiryAndConflict():void{$f=$this->f;$q=$f->request();$first=$f->ledger->advance(json_encode($q));$f->f->now+=2000;$head=$f->f->head();$again=$f->ledger->advance(json_encode($q,JSON_PRETTY_PRINT));self::assertSame($first['result_ref'],$again['result_ref']);self::assertSame('HISTORICAL_RECOGNITION',$again['status']);self::assertSame($head,$f->f->head());$q['mode']='preview';$this->refuses(fn()=>$f->ledger->advance(json_encode($q)),'COMMAND_CONFLICT');}
    public function testPreviewAndStatusCannotReserveOrAct():void{$f=$this->f;$q=$f->request();$q['mode']='preview';$head=$f->f->head();self::assertNull($f->ledger->advance(json_encode($q))['result_ref']);self::assertSame($head,$f->f->head());$f->advance();$head=$f->f->head();self::assertNull($f->recovery->status('sequence-test')['command_id']);self::assertSame($head,$f->f->head());self::assertSame(['issue'=>0,'consume'=>0,'dispatch'=>0],$f->ports->counts);}
    #[DataProvider('badFields')]
    public function testClosedRequestFieldsRefuse(string $field,mixed $value):void{$q=$this->f->request();$q[$field]=$value;$head=$this->f->f->head();try{$this->f->ledger->advance(json_encode($q));self::fail();}catch(\InvalidArgumentException|\RuntimeException){self::assertSame($head,$this->f->f->head());}}
    public static function badFields():iterable{yield ['verified',true];yield ['instance_id','instance-foreign'];yield ['mode','execute'];yield ['step_id','../private'];yield ['evidence_refs',[['schema'=>'x','id'=>'source-123','digest'=>'sha256:'.str_repeat('0',64)]]];yield ['expected_head',['generation'=>1,'digest'=>str_repeat('a',64)]];yield ['schema','imperium.provider-onboarding-request/v1'];}
    public function testStaleHeadPredecessorAndNewSequenceCannotReset():void{$f=$this->f;$q=$f->request();$f->advance();$this->refuses(fn()=>$f->ledger->advance(json_encode($q)),'STALE_HEAD');$q=$f->request('configure');$q['predecessor_ref']=null;$this->refuses(fn()=>$f->ledger->advance(json_encode($q)),'STALE_PREDECESSOR');$q=$f->request();$q['sequence_id']='different-sequence';$q['predecessor_ref']=null;$this->refuses(fn()=>$f->ledger->advance(json_encode($q)),'SEQUENCE_ALREADY_REGISTERED');}
    public function testDefaultPortsNeverIssueAndUnknownReservationNeverRedispatches():void{$f=$this->f;$f->ready();$q=$f->request('access');$default=new CommandLedger($f->f->store);$this->refuses(fn()=>$default->advance(json_encode($q)),'COMPATIBLE_PRODUCER_MISSING');$r=$f->ledger->advance(json_encode($q));$again=$f->custody->advance(json_encode($q));self::assertSame('HISTORICAL_RECOGNITION',$again['status']);self::assertSame(['issue'=>0,'consume'=>0,'dispatch'=>0],$f->ports->counts);$f->last=$r['result_ref'];$this->refuses(fn()=>$f->advance('select-base'),'STEP_NOT_READY');}

    public function testRevokedCompletedSignedPrerequisiteBlocksDependentWork():void{
        $this->f->close();$this->f=$f=new F(signed:true);$f->ready();$s=$f->f->store->journal->read()['state']['onboarding'];$nonce=array_values($s['slots'])[0]['authority_key'][2];$f->f->revoke('act',$nonce);$this->refuses(fn()=>$f->advance('access'),'ACT_REVOKED');
    }

    public function testFiniteGroupInterpreterNeverCreatesRetryAuthorityOrResults():void{
        $f=$this->f;$s=$f->f->store->journal->read()['state']['onboarding'];$head=$f->f->head();$steps=array_column($f->policy['body']['steps'],null,'step_id');
        \App\Imperium\Runtime\Onboarding\Ledger\AssessmentGroups::ready($s,$f->policy,$steps['w1.attempt.0']);
        foreach(['w1.attempt.1','w1.attempt.2','w1.attempt.3'] as $id){try{\App\Imperium\Runtime\Onboarding\Ledger\AssessmentGroups::ready($s,$f->policy,$steps[$id]);self::fail();}catch(\RuntimeException $e){self::assertSame('O2_RETRY_REASON_NOT_ADMITTED',$e->getMessage());}}
        foreach(['W1','W2','W3'] as $g){try{\App\Imperium\Runtime\Onboarding\Ledger\AssessmentGroups::success($s,$f->policy,$g);self::fail();}catch(\RuntimeException $e){self::assertSame('O2_GROUP_NOT_READY',$e->getMessage());}}
        self::assertSame($head,$f->f->head());self::assertSame([],$s['claims']);
    }

    public function testNewPolicySequenceCannotBypassOriginalUnknownFence():void{
        $f=$this->f;$f->ready();$f->advance('access');$policy=$f->policy;unset($policy['record_digest']);$policy['id']='policy-replacement';$policy=R::seal($policy);
        (new Admission($f->f->store))->retain($f->f::json($f->sign($policy,'AUTHORIZE_BOOTSTRAP_POLICY')),$f->f::json($policy),array_map($f->f::json(...),array_values($f->f->sources)));
        $q=$f->request();$q['sequence_id']='second-sequence';$q['predecessor_ref']=null;$q['policy_ref']=['id'=>$policy['id'],'version'=>$policy['body']['policy_version'],'digest'=>$policy['record_digest']];
        $r=$f->ledger->advance(json_encode($q));$h=$f->f->head();$h['digest']='sha256:'.$h['digest'];$q['expected_head']=$h;$q['command_id']='replacement-configure';$q['predecessor_ref']=$r['result_ref'];$q['step_id']='configure';$this->refuses(fn()=>$f->ledger->advance(json_encode($q)),'OUTCOME_UNKNOWN');
    }
}
