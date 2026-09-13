<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\AssignmentFixture as F;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules as R,Admission};
use App\Imperium\Runtime\Onboarding\Ledger\{CommandLedger,Recovery,CustodyCoordinator};
use App\Imperium\Runtime\Onboarding\Assignment\{SettingsBoundTransport,SettingsPreparedTransport};
use App\Imperium\Runtime\Citadel\Formation\PreparedFormationTransport;
use PHPUnit\Framework\TestCase;
final class AssignmentRefusalTest extends TestCase
{
    private function refuses(F $f,callable $action,?string $reason=null):void
    {
        $before=$f->fresh->d->f->store->journal->read();$calls=$f->requests;
        try{$action();self::fail('Expected refusal');}catch(\RuntimeException|\InvalidArgumentException $e){if($reason!==null){self::assertStringContainsString($reason,$e->getMessage());}else{self::assertNotSame('',$e->getMessage());}}
        self::assertSame($before,$f->fresh->d->f->store->journal->read());self::assertSame($calls,$f->requests);
    }
    public function testSignedModeRefusalsConsumerRecoveryAndRevokedHistory():void
    {
        $f=new F(applicationMode:'B');try{
            $f->assessed();$d=$f->fresh->d;$store=$d->f->store;$ledger=$f->ledger();$calls=$f->requests;
            $q=$d->request('apply-assignments');$run=fn(array $q)=>$ledger->advance($d->f::json($q));
            $this->refuses($f,fn()=>$run($q),'AUTHORITY_ORIGINAL_MISSING');
            $other=$d->f->sources[R::key($f->assignmentSets[3]['terms_ref'])];
            (new Admission($store))->retain($d->f::json($d->f->sign($other,'APPLY_BOOTSTRAP_ASSIGNMENTS',R::reference($d->policy))),$d->f::json($other));
            $q=$d->request('apply-assignments');$this->refuses($f,fn()=>$run($q),'AUTHORITY_ORIGINAL_MISSING');
            $wrong=$q;$wrong['expected_head']['generation']++;$this->refuses($f,fn()=>$run($wrong),'STALE_HEAD');
            $wrong=$q;$wrong['predecessor_ref']=null;$this->refuses($f,fn()=>$run($wrong),'STALE_PREDECESSOR');
            $this->refuses($f,fn()=>(new CommandLedger($store,$f->adapter,$f->adapter,$f->fresh->founding()))->advance($d->f::json($q)),'CURRENT_ASSIGNMENT_EVIDENCE_MISSING');
            $terms=$d->f->sources[R::key($f->assignmentSets[0]['terms_ref'])];
            $act=(new Admission($store))->retain($d->f::json($d->f->sign($terms,'APPLY_BOOTSTRAP_ASSIGNMENTS',R::reference($d->policy))),$d->f::json($terms));
            $q=$d->request('apply-assignments');
            $now=$d->f->now;foreach([1801,10001] as $elapsed){try{$d->f->now=$now+$elapsed;$this->refuses($f,fn()=>$run($q));}finally{$d->f->now=$now;}}
            foreach(['profileGeneration','bindingGeneration'] as $field){$f->$field=2;$this->refuses($f,fn()=>$run($q),'SYNTHETIC_GENERATION');$f->$field=1;}
            $f->available=false;$this->refuses($f,fn()=>$run($q),'MODEL_UNAVAILABLE');$f->available=true;
            $pins=$f->assignmentPins;$f->assignmentPins=[];$this->refuses($f,fn()=>$run($q),'ASSIGNMENT_ORIGINAL');$f->assignmentPins=$pins;
            $clean=$store->journal->read()['state'];$claimKey=array_key_last($clean['onboarding']['claims']);$sourceKey=array_key_first($clean['onboarding']['evidence']);
            $corruptions=[
                static function(array &$s)use($claimKey):void{unset($s['onboarding']['claims'][$claimKey]['custody'][4]['body']['response_envelope']['response']);},
                static function(array &$s)use($sourceKey):void{$s['onboarding']['evidence'][$sourceKey]['raw']='{}';},
                static function(array &$s):void{$s['onboarding']['source_fences']['synthetic-broken-fence']=[];},
                static function(array &$s):void{$s['onboarding']['applications']['unauthorized-writer']=[];},
            ];
            foreach($corruptions as $corrupt){try{$store->journal->change($corrupt);$badRequest=$q;$badRequest['expected_head']=$f->head();$this->refuses($f,fn()=>$run($badRequest));}finally{$store->journal->change(static function(array &$s)use($clean):void{$s=$clean;});}}
            $q=$d->request('apply-assignments');
            $out=$run($q);self::assertSame('STEP_ADMITTED',$out['status']);$settings=$f->settings();$snapshot=$settings->snapshot();self::assertEquals($snapshot,$settings->revalidate($snapshot['application_ref']));
            $conflict=$q;$conflict['expected_head']=$f->head();$this->refuses($f,fn()=>$run($conflict),'COMMAND_CONFLICT');
            $duplicate=$d->request('apply-assignments','duplicate-application');$this->refuses($f,fn()=>$run($duplicate));
            $sink=new class implements PreparedFormationTransport {
                public int $calls=0;
                public function inspect(array $request,array $terms):array{$this->calls++;return ['accepted'=>'inspect'];}
                public function invoke(array $claim,array $request,array $terms):array{$this->calls++;return ['accepted'=>'invoke'];}
                public function prepareOperation(array $request,array $terms):array{$this->calls++;return ['accepted'=>'prepare'];}
            };
            $this->refuses($f,fn()=>new SettingsBoundTransport($settings,$sink,'courtyard.courtthane'),'PREPARED_WRAPPER_REQUIRED');
            $consumer=new SettingsPreparedTransport($settings,$sink,'courtyard.courtthane');$tuple=$snapshot['assignments'][0];$t=['provider'=>$tuple['provider'],'model'=>$tuple['model_id'],'model_settings'=>$tuple];
            self::assertSame(['accepted'=>'inspect'],$consumer->inspect([],$t));self::assertSame(['accepted'=>'prepare'],$consumer->prepareOperation([],$t));self::assertSame(['accepted'=>'invoke'],$consumer->invoke([],[],$t));
            $bad=$t;$bad['model_settings']['generation']++;$this->refuses($f,fn()=>$consumer->invoke([],[],$bad),'TRANSPORT_GENERATION');self::assertSame(3,$sink->calls);
            $f->available=false;$this->refuses($f,fn()=>$consumer->invoke([],[],$t),'MODEL_UNAVAILABLE');$f->available=true;self::assertSame(3,$sink->calls);
            $replacement=$f->replacementRequest();$wrong=$replacement;$wrong['authority']=[];$this->refuses($f,fn()=>$ledger->replace($d->f::json($wrong)));
            $wrong=$replacement;$wrong['terms_ref']=$f->assignmentSets[0]['terms_ref'];$this->refuses($f,fn()=>$ledger->replace($d->f::json($wrong)));
            self::assertSame('ASSIGNMENTS_CHANGED',$ledger->replace($d->f::json($replacement))['status']);$next=$settings->snapshot();
            $this->refuses($f,fn()=>$settings->revalidate($snapshot['application_ref']),'STALE_ASSIGNMENT_PREDECESSOR');
            $this->refuses($f,fn()=>$consumer->invoke([],[],$t),'TRANSPORT_IDENTITY');self::assertSame(3,$sink->calls);
            $stale=$replacement;$stale['command_id']='stale-replacement';$stale['expected_head']=$f->head();$this->refuses($f,fn()=>$ledger->replace($d->f::json($stale)),'STALE_ASSIGNMENT_PREDECESSOR');
            $recovery=new Recovery($ledger,new CustodyCoordinator($ledger));$resume=['schema'=>'imperium.provider-onboarding-resume/v2','sequence_id'=>$q['sequence_id'],'command_id'=>'recover-application','expected_head'=>$f->head(),'recognize_command_ref'=>$out['result_ref']];
            self::assertSame('EVIDENCE_RECOGNITION',$recovery->resume($d->f::json($resume))['status']);self::assertEquals($next,$settings->snapshot());
            $state=$store->journal->read()['state']['onboarding'];$nonce=null;foreach($state['admissions'] as $key=>$receipt){if(R::same(R::reference($receipt),$replacement['authority']['admission_ref'])){$nonce=Admission::retained($state,$key)['envelope']['payload']['nonce'];}}
            self::assertIsString($nonce);$d->f->revoke('act',$nonce);$before=$store->journal->read();
            $this->refuses($f,fn()=>$settings->resolve('courtyard.courtthane'));
            self::assertSame('HISTORICAL_RECOGNITION',$run($q)['status']);self::assertSame('HISTORICAL_RECOGNITION',$ledger->replace($d->f::json($replacement))['status']);
            self::assertSame('HISTORICAL_RECOGNITION',$recovery->resume($d->f::json($resume))['status']);self::assertSame($before,$store->journal->read());self::assertEquals($next,$settings->snapshot());self::assertSame($calls,$f->requests);
            foreach([['policy',$d->policy['id']],['issuer','operator-test']] as [$kind,$id]){$d->f->revoke($kind,$id);$before=$store->journal->read();$this->refuses($f,fn()=>$settings->resolve('courtyard.courtthane'));self::assertSame('HISTORICAL_RECOGNITION',$run($q)['status']);self::assertSame('HISTORICAL_RECOGNITION',$ledger->replace($d->f::json($replacement))['status']);self::assertSame($before,$store->journal->read());self::assertEquals($next,$settings->snapshot());}
        }finally{$f->close();}
    }
    public static function selections():iterable{yield 'zero'=>['no-fit',false];yield 'unknown'=>['unknown',false];yield 'coupled'=>['coupled',false];yield 'sole'=>['sole',true];yield 'lower middle'=>['ranked',true];}
    #[\PHPUnit\Framework\Attributes\DataProvider('selections')]
    public function testProducerBackedSelectionBoundaries(string $mode,bool $selected):void
    {
        $f=new F($mode);try{$f->assessed();$d=$f->fresh->d;$q=$d->request('apply-assignments');$calls=$f->requests;
            if(!$selected){$this->refuses($f,fn()=>$f->ledger()->advance($d->f::json($q)));self::assertSame([],$d->f->store->journal->read()['state']['onboarding']['applications']);}
            else{self::assertSame('STEP_ADMITTED',$f->ledger()->advance($d->f::json($q))['status']);foreach($f->settings()->snapshot()['assignments'] as $tuple){self::assertSame('deepseek-v4-flash',$tuple['model_id']);}}
            self::assertSame($calls,$f->requests);
        }finally{$f->close();}
    }
}
