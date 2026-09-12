<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Onboarding\DeepSeek\{KeySource,Runtime};
use App\Imperium\Runtime\Onboarding\Ledger\CommandLedger;
use App\Tests\Imperium\Runtime\Support\DeepSeekFixture as F;
use PHPUnit\Framework\{TestCase,Attributes\DataProvider};
use Symfony\Component\HttpClient\MockHttpClient;

final class DeepSeekCredentialCorrectionTest extends TestCase
{
    #[DataProvider('sources')]
    public function testIndependentDeliverySourceRefusesBeforeReservation(string $generation): void
    {
        $f=new F(); try {
            $f->ready(); $head=$f->f->head();
            $foreign=new class($generation) implements KeySource {
                public int $deliveries=0;
                public function __construct(private string $generation) {}
                public function generation(): string { return $this->generation; }
                public function withKey(callable $delivery): void { ++$this->deliveries; }
            };
            try {
                new Runtime($f->f->store,$f->adapter,$generation==='missing'?null:$foreign,$f->envelopes,new MockHttpClient());
                self::fail('Distinct source must refuse, including equal public generation text');
            } catch (\RuntimeException $e) { self::assertSame('O2_DEEPSEEK_DELIVERY_SOURCE_MISMATCH',$e->getMessage()); }
            self::assertSame(0,$foreign->deliveries);
            self::assertSame($head,$f->f->head());
            self::assertSame([],$f->f->store->journal->read()['state']['onboarding']['claims']);
            self::assertSame([],$f->requests);
            self::assertSame([],glob($f->f->root.'/responses/*.json'));
        } finally { $f->close(); }
    }
    public static function sources(): iterable { foreach (['foreign-generation','synthetic-generation','missing'] as $g) { yield [$g]; } }

    #[DataProvider('boundaries')]
    public function testDeliverySourceRotationCannotCrossBoundaryOrRecoverDelivery(string $boundary): void
    {
        $f=new F(); try {
            $f->ready();
            (new CommandLedger($f->f->store,$f->adapter,$f->adapter))->advance($f->f::json($f->request('access')));
            $id=array_key_first($f->f->store->journal->read()['state']['onboarding']['claims']);
            // Privileged test harness stops the real coordinator at its actual committed boundaries.
            $coordinator=(new \ReflectionProperty(Runtime::class,'coordinator'))->getValue($f->runtime);
            $checkpoint=new \ReflectionMethod($coordinator,'checkpoint');
            $checkpoint->invoke($coordinator,$id,0);
            $claim=$f->claim(); $op=$claim['operation']['prepared']; $record=$claim['record'];
            $issue=new \ReflectionMethod(Runtime::class,'issue');
            $consume=new \ReflectionMethod(Runtime::class,'consume');
            $dispatch=new \ReflectionMethod(Runtime::class,'dispatch');
            $cap=null;
            if ($boundary!=='issue') {
                $cap=$issue->invoke($f->runtime,$record,$op);
                $checkpoint->invoke($coordinator,$id,1);
            }
            if ($boundary==='callback') { $f->keyHook=static function()use($f):void { $f->keys->version='rotated'; }; }
            elseif ($boundary!=='dispatch') { $f->keys->version='rotated'; }
            try {
                if ($boundary==='issue') { $issue->invoke($f->runtime,$record,$op); }
                else {
                    $consume->invoke($f->runtime,$cap,$record,$op,static function(#[\SensitiveParameter] string $key)use($f,$boundary,$checkpoint,$coordinator,$id,$dispatch,$op):void {
                        self::assertSame('dispatch',$boundary,'A rotated source cannot enter delivery');
                        $checkpoint->invoke($coordinator,$id,2);
                        $f->keys->version='rotated';
                        $dispatch->invoke($f->runtime,$op,$key);
                    });
                }
                self::fail('Rotation must refuse');
            } catch (\RuntimeException $e) {
                self::assertSame(match($boundary) {
                    'issue'=>'O2_DEEPSEEK_KEY_CURRENT',
                    'consume'=>'O2_CAPABILITY_SCOPE',
                    default=>'O3_KEY_CUSTODY_REFUSED',
                },$e->getMessage());
            }
            self::assertSame([],$f->requests);
            self::assertSame([],glob($f->f->root.'/responses/*.json'));
            $after=$f->claim(); self::assertNull($after['settled']);
            self::assertSame($claim['maximum'],$after['maximum']);
            self::assertCount($boundary==='issue'?1:($boundary==='dispatch'?3:2),$after['custody']);
            self::assertNotEmpty($f->f->store->journal->read()['state']['onboarding']['source_fences']);
            // Restoring generation and restarting cannot convert an uncertain claim into a new right.
            $f->keys->version='synthetic-generation'; $f->keyHook=null; $head=$f->f->head();
            $restart=new Runtime($f->f->store,$f->adapter,$f->keys,$f->envelopes,new MockHttpClient(static function(){self::fail('Recovery dispatched');}));
            try { $restart->reconcile($id);self::fail('Missing response must stay unknown'); }
            catch (\RuntimeException $e) { self::assertSame('O2_OUTCOME_UNKNOWN',$e->getMessage()); }
            self::assertSame($head,$f->f->head()); self::assertNull($f->claim()['settled']);
        } finally { $f->close(); }
    }
    public static function boundaries(): iterable { foreach (['issue','consume','callback','dispatch'] as $b) { yield [$b]; } }
}
