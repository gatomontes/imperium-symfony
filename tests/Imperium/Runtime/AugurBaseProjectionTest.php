<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\AugurProjectionFixture as F;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules as R;
use App\Imperium\Runtime\Onboarding\Ledger\LedgerState;
use PHPUnit\Framework\{TestCase,Attributes\DataProvider};

final class AugurBaseProjectionTest extends TestCase
{
    public function testActualOriginalsDriveO1AndO2CompletionWithoutAnotherProviderCall():void
    {
        $f=new F();try{
            $f->ready();$d=$f->d;$q=$d->request('select-base');
            $step=array_values(array_filter($d->policy['body']['steps'],static fn($s)=>$s['step_id']==='select-base'))[0];
            $proposal=$f->base->propose($d->f->store,$d->f->store->journal->read()['state'],$d->policy,$step);
            self::assertSame('PROPOSED_BASE',$proposal->status);self::assertSame('deepseek-v4-flash',$proposal->proposedBinding['model_id']);
            self::assertSame([37848,113544],array_column($proposal->rows,'baseline_micro_usd'));
            $result=$d->runtime->advance($d->f::json($q));$s=$d->f->store->journal->read()['state']['onboarding'];
            $entry=LedgerState::step($s,$d->policy,'select-base');
            self::assertSame(R::refs([$f->manifest,$proposal->proposedBinding['binding_ref']]),$entry['completion']['body']['result_refs']);
            self::assertSame([['method'=>'GET','url'=>'https://api.deepseek.com/models']],$d->requests);
            self::assertSame([],$s['bindings']);self::assertSame([],$s['applications']);
            self::assertSame(array_fill_keys(['deployment_approved','enrollment_authorized','live_ready','activation','execution_authority'],false),$result['operational_flags']);
            $head=$d->f->head();self::assertSame('HISTORICAL_RECOGNITION',$d->runtime->advance($d->f::json($q))['status']);self::assertSame($head,$d->f->head());
            $d->last=$result['result_ref'];$d->advance('map-base');
            $s=$d->f->store->journal->read()['state']['onboarding'];
            self::assertSame([$proposal->proposedBinding['mapping_ref']],LedgerState::step($s,$d->policy,'map-base')['completion']['body']['result_refs']);
            $head=$d->f->head();
            try{$d->advance('found-augur');self::fail('A base proposal is not a FRESH producer');}
            catch(\RuntimeException $e){self::assertSame('O2_DYNAMIC_PREREQUISITES_MISSING',$e->getMessage());}
            self::assertSame($head,$d->f->head());self::assertCount(1,$d->requests);
        }finally{$f->close();}
    }
    #[DataProvider('refusals')]
    public function testRefusalPublishesNoBaseCompletion(string $case,string $reason):void
    {
        $f=new F($case);try{
            $f->ready();$d=$f->d;$head=$d->f->head();
            try{$d->advance('select-base');self::fail('Invalid base evidence must refuse');}
            catch(\RuntimeException $e){self::assertSame($reason,$e->getMessage());}
            self::assertSame($head,$d->f->head());self::assertCount(1,$d->requests);
            self::assertArrayNotHasKey(LedgerState::key('step',[$d->f->store->instance,$d->policy['record_digest'],'select-base']),$d->f->store->journal->read()['state']['onboarding']['steps']);
        }finally{$f->close();}
    }
    public static function refusals():iterable
    {
        yield ['missing-evidence','O3_AUTHENTIC_BASE_EVIDENCE_MISSING'];
        yield ['false-tokens','O2_SYNTHETIC_PROVIDER_ORIGINAL'];
        yield ['false-tariff','O2_SYNTHETIC_PROVIDER_ORIGINAL'];
        yield ['false-account','O2_SYNTHETIC_PROVIDER_ORIGINAL'];
        yield ['wrong-map','O2_BASE_MAPPING_SCOPE'];
        yield ['profile','O2_BASE_PROFILE'];yield ['version','O2_BASE_PROJECTION_VERSION'];
        yield ['no-fit','O2_BASE_NOT_ELIGIBLE'];yield ['stale','O2_BASE_NOT_ELIGIBLE'];
    }
    public function testRevokedAdmittingActCannotBeReusedForSelection():void
    {
        $f=new F();try{$f->ready();$d=$f->d;
            $s=$d->f->store->journal->read()['state']['onboarding'];
            $admission=$s['evidence'][R::key($f->observations[0])]['admission_key'];
            $d->f->revoke('act',$s['acts'][$admission]['envelope']['payload']['nonce']);$head=$d->f->head();
            try{$d->advance('select-base');self::fail();}catch(\RuntimeException $e){self::assertSame('O2_ACT_REVOKED',$e->getMessage());}
            self::assertSame($head,$d->f->head());self::assertCount(1,$d->requests);
        }finally{$f->close();}
    }
}
