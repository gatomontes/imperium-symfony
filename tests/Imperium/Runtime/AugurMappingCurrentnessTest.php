<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Tests\Imperium\Runtime\Support\AugurMappingFixture as F;
use App\Imperium\Runtime\Onboarding\Ledger\AssessmentGroups;
use PHPUnit\Framework\TestCase;

final class AugurMappingCurrentnessTest extends TestCase
{
    public function testMappingExpiresBetweenCredentialConsumptionAndCallback():void
    {
        $f=new F(['calls'=>1,'input_tokens'=>16384,'output_tokens'=>4096,'cost_microusd'=>100000,'milliseconds'=>60000],mappingLifetime:300);
        try{
            $f->ready();$d=$f->fresh->d;
            // A genuine clock transition at the existing delivery hook, not a forged state/map.
            $d->keyHook=function()use($d):void{$d->f->now+=301;};
            try{$d->advance('w1.attempt.0');self::fail('Expired original mapping cannot dispatch');}
            catch(\RuntimeException $e){self::assertSame('O2_CUSTODY_REFUSED_OR_OUTCOME_UNKNOWN',$e->getMessage());}
            $s=$d->f->store->journal->read()['state']['onboarding'];
            self::assertSame(['GET'],array_column($f->requests,'method'));
            self::assertNull(AssessmentGroups::outcome($s,$d->policy,'w1.attempt.0'));
            $claims=array_values(array_filter($s['claims'],static fn(array $c):bool=>$c['record']['body']['authority_source']['kind']==='assessment'));
            self::assertCount(1,$claims);self::assertNull($claims[0]['settled']);
            self::assertCount(1,$s['source_fences']);
        }finally{$f->close();}
    }
}
