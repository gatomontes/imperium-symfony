<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\AugurCognitionFixture as F;
use App\Imperium\Runtime\Onboarding\Ledger\{AssessmentGroups,LedgerState};
use PHPUnit\Framework\{TestCase,Attributes\DataProvider};
final class AugurCognitionRefusalTest extends TestCase
{
    #[DataProvider('responses')]
    public function testSemanticOutcomeAndUnknownUsage(string $mode,?string $classification):void
    {
        $f=new F($mode);try{$f->ready();$d=$f->fresh->d;
            try{$d->advance('w1.attempt.0');self::assertNotNull($classification);}catch(\RuntimeException $e){self::assertNull($classification);self::assertSame('O2_CUSTODY_REFUSED_OR_OUTCOME_UNKNOWN',$e->getMessage());}
            $s=$d->f->store->journal->read()['state']['onboarding'];self::assertSame(['GET','POST'],array_column($f->requests,'method'));
            $outcome=AssessmentGroups::outcome($s,$d->policy,'w1.attempt.0');
            if($classification===null){self::assertNull($outcome);self::assertCount(1,$s['source_fences']);$assessment=array_values(array_filter($s['claims'],static fn(array $c):bool=>$c['record']['body']['authority_source']['kind']==='assessment'))[0];self::assertNull($assessment['settled']);self::assertCount(3,$assessment['custody']);}
            else{self::assertSame($classification,$outcome['classification']);self::assertSame([],$s['source_fences']);}
            if($classification!=='SUCCEEDED'){
                $head=$d->f->head();try{$d->advance('w2.attempt.0');self::fail('Unsuccessful W1 cannot authorize W2');}catch(\RuntimeException){}self::assertSame($head,$d->f->head());
                try{$d->advance('w1.attempt.1');self::fail('The retry allowlist is empty');}catch(\RuntimeException){}self::assertCount(2,$f->requests);
            }
            self::assertSame([],$s['applications']);
        }finally{$f->close();}
    }
    public static function responses():iterable{yield ['no-fit','SUCCEEDED'];yield ['invalid','TERMINAL_FAILURE'];yield ['missing-usage',null];yield ['revoked-after-dispatch',null];yield ['token-contradiction',null];}
    public function testActualDeliveryGenerationChangeRefusesBeforeAnyPost():void
    {
        $f=new F();try{$f->ready();$d=$f->fresh->d;$head=$d->f->head();$d->keys->version='rotated-generation';
            try{$d->advance('w1.attempt.0');self::fail('An account grant cannot authorize another credential generation');}catch(\RuntimeException $e){self::assertSame('O2_DEEPSEEK_KEY_CURRENT',$e->getMessage());}
            self::assertSame($head,$d->f->head());self::assertSame(['GET'],array_column($f->requests,'method'));
        }finally{$f->close();}
    }
    #[DataProvider('commissions')]
    public function testCurrentHolderAndIndependentCommissionRefuseBeforeReservation(string $mode,string $reason):void
    {
        $f=new F($mode);try{$f->ready();$d=$f->fresh->d;if($mode==='expired-holder'){$d->f->now+=201;}$head=$d->f->head();
            try{$d->advance('w1.attempt.0');self::fail('Invalid commission or holder must not reserve');}catch(\RuntimeException $e){self::assertSame($reason,$e->getMessage());}
            self::assertSame($head,$d->f->head());self::assertSame(['GET'],array_column($f->requests,'method'));
        }finally{$f->close();}
    }
    public static function commissions():iterable{
        yield ['missing-evidence','O3_AUTHENTIC_COGNITION_EVIDENCE_MISSING'];yield ['expired-commission','O2_COMMISSION_SCOPE'];
        yield ['wrong-constitution','O2_COMMISSION_HOLDER'];yield ['expired-holder','O2_HOLDER_CURRENT'];
    }
}
