<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Tests\Imperium\Runtime\Support\AugurCognitionFixture as F;
use App\Imperium\Runtime\Onboarding\Assignment\AssessmentResolver;
use App\Imperium\Runtime\Onboarding\Augur\AugurAdapter;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore,Rules as R};
use App\Imperium\Runtime\Onboarding\ResponseValidation\ParsedResponse;
use PHPUnit\Framework\TestCase;

final class AssignmentAssessmentResolverTest extends TestCase
{
    public function testRealHistoryResolvesExactOriginalsWithoutCallsOrMutationAndRechecksCurrentness():void
    {
        $f=new F();
        try {
            $f->ready();$d=$f->fresh->d;$store=$d->f->store;
            $resolver=new AssessmentResolver($store,$f->adapter);
            $ref=R::reference($d->policy);
            $this->refused(fn()=>$resolver->resolve($ref),'O2_GROUP_NOT_READY');
            foreach(['w1.attempt.0','w2.attempt.0','w3.attempt.0'] as $step){$d->advance($step);}
            $before=$store->journal->read();$calls=$f->requests;
            $result=$resolver->resolve($ref);
            self::assertEquals($d->policy,$result['policy']);
            self::assertSame($d->policy['record_digest'],$result['policy']['record_digest']);
            self::assertSame(['W1','W2','W3'],array_column(array_column($result['groups'],'outcome'),'group_id'));
            self::assertCount(3,$result['lineage']);
            foreach($result['groups'] as $i=>$group){
                self::assertInstanceOf(ParsedResponse::class,$group['response']);
                self::assertSame($result['lineage'][$i],R::reference($group['outcome']));
                self::assertEquals(R::reference($group['claim']),$group['outcome']['claim_ref']);
                self::assertEquals([R::reference($group['checkpoint'])],$group['outcome']['response_refs']);
                self::assertEquals(R::reference($group['checkpoint']),$group['outcome']['usage_ref']);
                self::assertEquals($ref,$group['frozen_input']['body']['policy_ref']);
                $raw=json_decode($group['checkpoint']['body']['response_envelope']['response'],true,512,JSON_THROW_ON_ERROR);
                self::assertSame($raw['choices'][0]['message']['content'],$group['response']->rawBytes);
                self::assertSame('sha256:'.hash('sha256',$group['response']->rawBytes),$group['response']->rawDigest);
                self::assertSame(array_column($d->policy['body']['candidate_bindings'],'binding_ref'),array_map(
                    static fn($row):array=>['schema'=>$row->bindingRef->schema,'id'=>$row->bindingRef->id,'digest'=>$row->bindingRef->digest],
                    $group['response']->candidateRows));
                if($i>0){self::assertSame($d->policy['body']['targets'][$i-1]['profile_ref']['digest'],$group['response']->profileRef->digest);}
            }
            // A separately constructed store resolves the same persisted originals.
            $restart=new AuthorityStore($d->f->root,$store->clock,$store->instance,$store->citadel,$store->operator,$store->sourceCommit);
            self::assertEquals($result,(new AssessmentResolver($restart,$f->adapter))->resolve($ref));
            // Stored success labels cannot replace the selected semantic-evidence verifier.
            $missing=new AugurAdapter($d->adapter,$f->fresh->founding(),$d->keys);
            $this->refused(fn()=>(new AssessmentResolver($store,$missing))->resolve($ref),'O3_AUTHENTIC_COGNITION_EVIDENCE_MISSING');
            self::assertSame($before,$store->journal->read());
            self::assertSame($calls,$f->requests);
            // Negative corruption only: every positive prerequisite above was produced,
            // never seeded. Restore the exact captured original after each corruption.
            $claimKey=array_key_last($before['state']['onboarding']['claims']);
            $groupKey=array_key_last($before['state']['onboarding']['group_inputs']);
            $mutations=[
                static function(array &$state)use($claimKey):void{unset($state['onboarding']['claims'][$claimKey]['custody'][4]['body']['response_envelope']['response']);},
                static function(array &$state)use($claimKey):void{$state['onboarding']['claims'][$claimKey]['settled']=null;},
                static function(array &$state)use($groupKey):void{
                    $h=&$state['onboarding']['group_inputs'][$groupKey];unset($h['record_digest']);
                    $h['body']['semantic_input_digest']='sha256:'.str_repeat('0',64);$h=R::seal($h);
                },
                static function(array &$state)use($claimKey):void{$state['onboarding']['claims'][$claimKey]['operation']['prepared']['model']='deepseek-v4-pro';},
            ];
            foreach($mutations as $mutate){
                try{
                    $store->journal->change($mutate);$corrupt=$store->journal->read();
                    $this->refused(fn()=>$resolver->resolve($ref));
                    self::assertSame($corrupt,$store->journal->read());
                    self::assertSame($calls,$f->requests);
                }finally{$store->journal->change(static function(array &$state)use($before):void{$state=$before['state'];});}
            }
            // Current time is never cached by a prior successful resolution.
            $now=$d->f->now;
            try{$d->f->now+=1801;$this->refused(fn()=>$resolver->resolve($ref));}
            finally{$d->f->now=$now;}
            $d->f->revoke('policy',$d->policy['id']);$revoked=$store->journal->read();
            $this->refused(fn()=>$resolver->resolve($ref),'O2_POLICY_REVOKED');
            self::assertSame($revoked,$store->journal->read());
            self::assertSame($calls,$f->requests);
            self::assertSame([],$revoked['state']['onboarding']['applications']);
        } finally {$f->close();}
    }

    private function refused(callable $action,?string $message=null):void
    {
        try{$action();self::fail('Expected refusal '.$message);}
        catch(\RuntimeException|\InvalidArgumentException $e){
            if($message===null){self::assertNotSame('',$e->getMessage());}
            else{self::assertStringContainsString($message,$e->getMessage());}
        }
    }
}
