<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{StrictJson,Rules as R};
use App\Tests\Imperium\Runtime\Support\OnboardingAuthorityFixture as F;
use PHPUnit\Framework\TestCase;

final class OnboardingParseScopeTest extends TestCase
{
    private function refuses(callable $call,string $reason):void
    {
        try{$call();self::fail('Invalid or noncurrent original must refuse');}
        catch(\RuntimeException|\InvalidArgumentException $e){self::assertStringContainsString($reason,$e->getMessage());}
    }
    // Inspect only pure parser storage, never fabricate a positive authority path.
    private function entries():?array{return (new \ReflectionProperty(StrictJson::class,'decoded'))->getValue();}

    public function testExactBytesCopyIsolationAndFinallyLifetime():void
    {
        self::assertNull($this->entries());
        try{StrictJson::within(function():void{
            $raw='{"item":{"value":1}}';$a=StrictJson::decode($raw);$a['item']['value']=9;
            self::assertSame(['item'=>['value'=>1]],StrictJson::decode($raw));
            self::assertCount(1,$this->entries());
            StrictJson::within(function()use($raw):void{self::assertSame(['item'=>['value'=>1]],StrictJson::decode($raw));});
            self::assertCount(1,$this->entries());
            self::assertSame(['item'=>['value'=>2]],StrictJson::decode('{"item":{"value":2}}'));
            self::assertSame(['item'=>['value'=>1]],StrictJson::decode(' {"item":{"value":1}}'));
            self::assertCount(3,$this->entries());
            $this->refuses(fn()=>StrictJson::decode('{"item":1,"item":1}'),'DUPLICATE_JSON_MEMBER');
            $this->refuses(fn()=>StrictJson::decode(str_repeat('[',33).'1'.str_repeat(']',33)),'RESPONSE_DEPTH_LIMIT');
            $this->refuses(fn()=>StrictJson::decode(str_repeat(' ',1048577)),'RESPONSE_BYTE_LIMIT');
            throw new \LogicException('scope exit');
        });}catch(\LogicException $e){self::assertSame('scope exit',$e->getMessage());}
        self::assertNull($this->entries());
        StrictJson::within(function():void{self::assertSame([],$this->entries());});
        self::assertNull($this->entries());
    }

    public function testStorageRemainsBoundedAndParsingContinuesWhenFull():void
    {
        StrictJson::within(function():void{
            for($i=0;$i<150;$i++){self::assertSame(['value'=>$i],StrictJson::decode('{"value":'.$i.'}'));}
            self::assertCount(128,$this->entries());
        });
        StrictJson::within(function():void{
            for($i=0;$i<3;$i++){$raw=json_encode(['value'=>str_repeat((string)$i,900000)],JSON_THROW_ON_ERROR);self::assertSame(str_repeat((string)$i,900000),StrictJson::decode($raw)['value']);}
            self::assertCount(2,$this->entries());
            self::assertLessThanOrEqual(2097152,(new \ReflectionProperty(StrictJson::class,'decodedBytes'))->getValue());
        });
        self::assertNull($this->entries());
    }

    public function testWarmParsesDoNotPreserveAuthorityAcrossClockOrRevocation():void
    {
        $f=new F();try{
            $f->enroll();[$p]=$f->admitPolicy();$ref=R::reference($p);
            StrictJson::within(function()use($f,$p,$ref):void{
                $s=$f->store->journal->read()['state']['onboarding'];
                self::assertTrue(R::same($p,$f->store->checkSource($s,$ref)));$now=$f->now;
                $f->now=$p['body']['expires_at'];$this->refuses(fn()=>$f->store->checkSource($s,$ref),'ACT_TIME');$f->now=$now;
                $f->revoke('policy',$p['id']);$current=$f->store->journal->read()['state']['onboarding'];
                $this->refuses(fn()=>$f->store->checkSource($current,$ref),'POLICY_REVOKED');
            });
        }finally{$f->close();}
    }

    public function testWarmParsesStillCheckMutatedOriginalBytesAndGraphBounds():void
    {
        $f=new F();try{
            $f->enroll();[$p]=$f->admitPolicy();$ref=R::reference($p);$key=R::key($ref);
            StrictJson::within(function()use($f,$p,$ref,$key):void{
                $s=$f->store->journal->read()['state']['onboarding'];self::assertTrue(R::same($p,$f->store->checkSource($s,$ref)));
                $bad=$s;$bad['policies'][$key]['raw']='{"invalid":1}';
                $this->refuses(fn()=>$f->store->checkSource($bad,$ref),'RETAINED_BYTES');
                $bad=$s;$bad['policies'][$key]['record']['body']['provider']='another';
                $this->refuses(fn()=>$f->store->checkSource($bad,$ref),'RECORD_DIGEST');
                $this->refuses(fn()=>$f->store->checkSource($s,$ref,[$key=>true]),'SOURCE_CYCLE');
                $this->refuses(fn()=>$f->store->checkSource($s,$ref,[],17),'SOURCE_DEPTH');
                $visits=8192;$this->refuses(function()use($f,$s,$ref,&$visits):void{$f->store->checkSource($s,$ref,[],0,$visits);},'SOURCE_VISIT_LIMIT');
                self::assertSame(8193,$visits);
            });
        }finally{$f->close();}
    }

    public function testEncodingReuseRequiresTheEntireImmutableParsedValue():void
    {
        $f=new F();try{
            $record=$f->store->make('imperium.synthetic-cache-record/v1','record-cache-test',['value'=>1]);
            StrictJson::within(function()use($record):void{
                $decoded=StrictJson::decode(F::json($record));
                self::assertSame(\App\Bootstrap\CanonicalJson::encode($decoded),StrictJson::canonical($decoded));
                $unsigned=$decoded;unset($unsigned['record_digest']);
                self::assertSame(\App\Bootstrap\CanonicalJson::encode($unsigned),StrictJson::canonical($unsigned));
                $changed=$decoded;$alias=&$changed['body']['value'];$alias=2;
                self::assertSame('sha256:'.hash('sha256',\App\Bootstrap\CanonicalJson::encode($changed)),R::hash($changed));
                $this->refuses(fn()=>R::record($changed),'RECORD_DIGEST');
                self::assertSame($record['record_digest'],R::record($decoded)['record_digest']);
                self::assertSame(1,StrictJson::decode(F::json($record))['body']['value']);
                $changed=$decoded;$changed['record_digest']='sha256:'.str_repeat('0',64);
                self::assertSame(\App\Bootstrap\CanonicalJson::encode($changed),StrictJson::canonical($changed));
                $this->refuses(fn()=>R::record($changed),'RECORD_DIGEST');
                $changed=$decoded;$changed['body']['value']=(object)['z'=>2,'a'=>1];
                self::assertSame(\App\Bootstrap\CanonicalJson::encode($changed),StrictJson::canonical($changed));
                $invalid=$decoded;$invalid['body']['value']="\xFF";
                try{R::same($invalid,$invalid);self::fail('Encoding errors must remain visible');}catch(\JsonException){self::assertTrue(true);}
                $varying=new class implements \JsonSerializable {private int $calls=0;public function jsonSerialize():mixed{return ++$this->calls;}};
                self::assertFalse(R::same($varying,$varying),'Arbitrary objects must retain the original encoder behavior');
            });
        }finally{$f->close();}
    }

    public function testCanonicalStorageIsBoundedAndNeverSuppressesValidation():void
    {
        $f=new F();try{StrictJson::within(function()use($f):void{
            for($i=0;$i<70;$i++){
                $h=$f->store->make('imperium.synthetic-cache-record/v1','record-cache-'.str_pad((string)$i,5,'0',STR_PAD_LEFT),['value'=>str_repeat('x',16000)]);
                $v=StrictJson::decode(F::json($h));
                self::assertSame(\App\Bootstrap\CanonicalJson::encode($v),StrictJson::canonical($v));
                unset($v['record_digest']);self::assertSame(\App\Bootstrap\CanonicalJson::encode($v),StrictJson::canonical($v));
            }
            $bytes=(new \ReflectionProperty(StrictJson::class,'encodedBytes'))->getValue();
            self::assertGreaterThan(2000000,$bytes);self::assertLessThanOrEqual(2097152,$bytes);
        });self::assertSame(0,(new \ReflectionProperty(StrictJson::class,'encodedBytes'))->getValue());}finally{$f->close();}
    }
}
