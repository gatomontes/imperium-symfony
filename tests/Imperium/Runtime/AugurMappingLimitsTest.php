<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Tests\Imperium\Runtime\Support\AugurMappingFixture as F;
use App\Imperium\Runtime\Onboarding\Augur\MappingLimits;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules as R,Policy};
use App\Imperium\Runtime\Onboarding\Ledger\AssessmentGroups;
use App\Imperium\Runtime\Onboarding\DeepSeek\Wire;
use PHPUnit\Framework\{TestCase,Attributes\DataProvider};

final class AugurMappingLimitsTest extends TestCase
{
    // Independent approved values: do not derive expected boundaries from production constants.
    private const REQUIRED=['calls'=>1,'input_tokens'=>16384,'output_tokens'=>4096,'cost_microusd'=>100000,'milliseconds'=>60000];

    #[DataProvider('strictLimits')]
    public function testRestrictiveOriginalCannotPublishFoundingOrCognition(string $meter,int $limit):void
    {
        $limits=self::REQUIRED;$limits[$meter]=$limit;$f=new F($limits);
        try{
            $d=$f->fresh->d;$mapRef=$d->policy['body']['candidate_bindings'][0]['mapping_ref'];
            $before=$d->f->store->checkSource($d->f->store->journal->read()['state']['onboarding'],$mapRef);
            self::assertSame($limit,Policy::content($before,'runtime-binding-map')['mappings'][0]['supported_limits'][$meter]);
            $f->fresh->ready();$head=$d->f->head();
            try{$d->advance('select-base');self::fail('Restrictive map must exclude this workload');}
            catch(\RuntimeException $e){self::assertSame('O2_BASE_NOT_ELIGIBLE',$e->getMessage());}
            self::assertSame($head,$d->f->head());
            $s=$d->f->store->journal->read()['state']['onboarding'];
            self::assertSame($before,$d->f->store->checkSource($s,$mapRef));
            self::assertSame([],$s['bindings']);
            self::assertSame([],array_values(array_filter($s['claims'],static fn(array $c):bool=>$c['record']['body']['authority_source']['kind']==='assessment')));
            self::assertNull(AssessmentGroups::outcome($s,$d->policy,'w1.attempt.0'));
            self::assertSame([['method'=>'GET','url'=>'https://api.deepseek.com/models']],$f->requests);
        }finally{$f->close();}
    }
    public static function strictLimits():iterable
    {
        foreach(self::REQUIRED as $meter=>$limit){
            yield $meter.' below'=>[$meter,$limit-1];
            if($limit>1){yield $meter.' zero'=>[$meter,0];}
        }
    }

    #[DataProvider('meters')]
    public function testUnsupportedCheaperCandidateDoesNotExcludeSupportedAlternative(string $meter):void
    {
        $limits=self::REQUIRED;$limits[$meter]=0;$f=new F($limits,true);
        try{
            $d=$f->fresh->d;$state=$d->f->store->journal->read()['state'];
            $step=array_values(array_filter($d->policy['body']['steps'],static fn(array $s):bool=>$s['step_id']==='select-base'))[0];
            $proposal=$f->fresh->base->propose($d->f->store,$state,$d->policy,$step);
            self::assertSame('PROPOSED_BASE',$proposal->status);
            self::assertSame('deepseek-v4-pro',$proposal->proposedBinding['model_id']);
            self::assertContains('FAIL:ADAPTER_SUPPORT',$proposal->rows[0]['reasons']);
            self::assertTrue($proposal->rows[1]['eligible_projection']);
            self::assertSame([37848,113544],array_column($proposal->rows,'baseline_micro_usd'));
            self::assertSame(Wire::CONFIGURATION,$proposal->proposedBinding['request_config']);
            self::assertSame([],$f->requests);
        }finally{$f->close();}
    }
    public static function meters():iterable{foreach(array_keys(self::REQUIRED) as $meter){yield $meter=>[$meter];}}

    public function testAllExactBoundariesPermitRealCognitionWithoutChangingConfiguration():void
    {
        $f=new F(self::REQUIRED);
        try{
            $f->ready();$d=$f->fresh->d;$d->advance('w1.attempt.0');
            $s=$d->f->store->journal->read()['state']['onboarding'];
            self::assertSame('SUCCEEDED',AssessmentGroups::outcome($s,$d->policy,'w1.attempt.0')['classification']);
            $claims=array_values(array_filter($s['claims'],static fn(array $c):bool=>$c['record']['body']['authority_source']['kind']==='assessment'));
            self::assertCount(1,$claims);self::assertSame(self::REQUIRED,$claims[0]['maximum']);
            self::assertCount(5,$claims[0]['custody']);
            foreach(self::REQUIRED as $meter=>$maximum){self::assertLessThanOrEqual($maximum,$claims[0]['custody'][4]['body']['response_envelope']['metadata']['usage'][$meter]);}
            self::assertSame(['GET','POST'],array_column($f->requests,'method'));
            self::assertSame(4096,json_decode($f->wires[0],true,512,JSON_THROW_ON_ERROR)['max_tokens']);
            self::assertSame(self::REQUIRED,MappingLimits::MAXIMUM);
        }finally{$f->close();}
    }
}
