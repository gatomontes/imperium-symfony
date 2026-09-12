<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\AugurCognitionFixture as F;
use App\Imperium\Runtime\Onboarding\Ledger\{AssessmentGroups,LedgerState};
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules as R;
use PHPUnit\Framework\TestCase;
final class AugurCognitionBridgeTest extends TestCase
{
    public function testRealFreshAndSequentialGroupsUsePrivateCustodyAndExactMock():void
    {
        $f=new F();try{$f->ready();$d=$f->fresh->d;
            foreach(['W1','W2','W3'] as $group){fwrite(STDERR,"O3-B1 ".$group." start\n");$before=count($f->requests);$d->advance(strtolower($group).'.attempt.0');self::assertCount($before+1,$f->requests);
                $s=$d->f->store->journal->read()['state']['onboarding'];$outcome=AssessmentGroups::success($s,$d->policy,$group);self::assertSame('SUCCEEDED',$outcome['classification']);
                self::assertNotNull(LedgerState::step($s,$d->policy,strtolower($group).'.attempt.0')['completion']);
                fwrite(STDERR,"O3-B1 ".$group." validated\n");
            }
            self::assertSame(['GET','POST','POST','POST'],array_column($f->requests,'method'));self::assertCount(3,$f->wires);self::assertSame([],$s['source_fences']);self::assertSame([],$s['applications']);
            foreach($s['claims'] as $claim){self::assertCount(5,$claim['custody']);self::assertNotNull($claim['settled']);self::assertSame(R::hash($claim['operation']['prepared']),$claim['custody'][4]['body']['response_envelope']['operation_digest']);}
            foreach(array_slice($f->wires,1) as $wire){$v=json_decode($wire,true);$input=json_decode($v['messages'][1]['content'],true)['input'];$prior=array_values(array_filter($input['inputs'],static fn(array $i):bool=>isset($i['outcome'])));self::assertNotEmpty($prior);self::assertSame('SUCCEEDED',$prior[0]['outcome']['classification']);self::assertNotEmpty($prior[0]['envelope']['response']);}
        }finally{$f->close();}
    }
}
