<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\OnboardingLedgerFixture as F;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\StrictJson;
use PHPUnit\Framework\TestCase;
final class CustodyPureScopeTest extends TestCase
{
    private function assertClosed():void {
        self::assertNull((new \ReflectionProperty(StrictJson::class,'decoded'))->getValue());
    }
    public function testPureScopeEndsBeforeEveryExternalPortAndAfterFailure():void {
        foreach([false,true] as $refuse){
            $f=new F();try{
                $f->ready();$observed=[];
                $f->ports->hook=function(string $stage)use($f,$refuse,&$observed):void{
                    $observed[]=$stage;$this->assertClosed();
                    if($refuse && $stage==='consume'){$f->f->now+=1800;}
                };
                try{$f->advance('access',true);self::assertFalse($refuse);}catch(\RuntimeException $e){self::assertTrue($refuse);self::assertSame('O2_CUSTODY_REFUSED_OR_OUTCOME_UNKNOWN',$e->getMessage());}
                $this->assertClosed();self::assertContains('issue',$observed);self::assertContains('consume',$observed);
                self::assertSame($refuse?0:1,$f->ports->counts['dispatch']);
                if(!$refuse){self::assertContains('dispatch',$observed);}
            }finally{$f->close();$this->assertClosed();}
        }
    }
}
