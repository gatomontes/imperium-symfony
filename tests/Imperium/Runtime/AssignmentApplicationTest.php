<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\AssignmentFixture as F;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules as R,Admission};
use PHPUnit\Framework\TestCase;
final class AssignmentApplicationTest extends TestCase
{
    public function testAtomicApplicationPersistentResolutionAndExplicitReplacement():void
    {
        $f=new F();try{
            $f->assessed();$d=$f->fresh->d;$store=$d->f->store;$ledger=$f->ledger();$calls=$f->requests;
            $request=$d->request('apply-assignments');$out=$ledger->advance($d->f::json($request));
            self::assertSame('STEP_ADMITTED',$out['status']);$before=$store->journal->read();$settings=$f->settings();$snapshot=$settings->snapshot();
            self::assertCount(2,$snapshot['assignments']);self::assertFalse($snapshot['dispatch_authority']);
            foreach($snapshot['assignments'] as $i=>$tuple){self::assertSame(1,$tuple['generation']);self::assertSame('deepseek-v4-flash',$tuple['model_id']);self::assertEquals($d->policy['body']['targets'][$i]['profile_ref'],$tuple['profile_ref']);self::assertEquals($tuple,$settings->resolve($tuple['role']));}
            self::assertSame('HISTORICAL_RECOGNITION',$ledger->advance($d->f::json($request))['status']);self::assertSame($before,$store->journal->read());
            $f->available=false;try{$this->refused(fn()=>$settings->resolve('courtyard.courtthane'));}finally{$f->available=true;}
            self::assertEquals($snapshot,$settings->snapshot());self::assertSame($before,$store->journal->read());
            $scope=$d->f->source('assignment-change',['expected_application_ref'=>$snapshot['application_ref'],'prior_assignments'=>$snapshot['assignments'],
                'next_set_ref'=>$f->assignmentSets[3]['set_ref'],'assessment_view_ref'=>array_values($before['state']['onboarding']['applications'])[0]['receipt']['body']['result_ref']]);
            $terms=$store->make('imperium.bootstrap-proposed-terms/v1','replace-terms-0001',['effect'=>'APPLY_BOOTSTRAP_ASSIGNMENTS','terms'=>$scope,'required_completed_refs'=>[]],[$scope]);
            $admission=(new Admission($store))->retain($d->f::json($d->f->sign($terms,'APPLY_BOOTSTRAP_ASSIGNMENTS',R::reference($d->policy))),$d->f::json($terms),[$d->f::json($d->f->sources[R::key($scope)])]);
            $q=$d->request(null,'replace-command-01');$q['schema']='imperium.assignment-change/v1';$q['predecessor_ref']=$out['result_ref'];$q['authority']=$d->f::authority($admission);$q['terms_ref']=R::reference($terms);
            $changed=$ledger->replace($d->f::json($q));self::assertSame('ASSIGNMENTS_CHANGED',$changed['status']);$next=$settings->snapshot();
            foreach($next['assignments'] as $tuple){self::assertSame(2,$tuple['generation']);self::assertSame('deepseek-v4-pro',$tuple['model_id']);}
            self::assertCount(2,$store->journal->read()['state']['onboarding']['applications']);
            self::assertSame('HISTORICAL_RECOGNITION',$ledger->advance($d->f::json($request))['status']);
            self::assertSame('HISTORICAL_RECOGNITION',$ledger->replace($d->f::json($q))['status']);
            $d->f->now+=1801;$this->refused(fn()=>$settings->resolve('courtyard.courtthane'));self::assertEquals($next,$settings->snapshot());
            self::assertSame('HISTORICAL_RECOGNITION',$ledger->advance($d->f::json($request))['status']);self::assertSame('HISTORICAL_RECOGNITION',$ledger->replace($d->f::json($q))['status']);
            self::assertSame($calls,$f->requests);
        }finally{$f->close();}
    }
    private function refused(callable $action):void{try{$action();self::fail('Expected refusal');}catch(\RuntimeException|\InvalidArgumentException $e){self::assertNotSame('',$e->getMessage());}}
}
