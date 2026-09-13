<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules,StrictJson};
use App\Tests\Imperium\Runtime\Support\EntryStrictJsonIndex as Entry;
use PHPUnit\Framework\TestCase;
final class PureIndexComparisonCompatibilityTest extends TestCase
{
    private function outcome(callable $f):array {
        try{return ['value',$f()];}catch(\Throwable $e){return ['error',$e::class,$e->getCode(),$e->getMessage()];}
    }
    public function testScalarComparisonPreservesBytesErrorsAndNumericFormatting():void {
        $values=[null,true,false,0,1,-1,PHP_INT_MAX,PHP_INT_MIN,0.0,-0.0,1.0,1.25,INF,NAN,'','0','1','null','true','é/漢字',"\0\r\n\t",'"\\',"\xFF",[],['nested'=>"\xFF"]];
        foreach($values as $a){foreach($values as $b){
            self::assertSame($this->outcome(fn()=>StrictJson::canonical($a)===StrictJson::canonical($b)), $this->outcome(fn()=>Rules::same($a,$b)));
        }}
        foreach(range(0,255) as $byte){$s=chr($byte);foreach([$s,$s.'x',''] as $b){
            self::assertSame($this->outcome(fn()=>StrictJson::canonical($s)===StrictJson::canonical($b)), $this->outcome(fn()=>Rules::same($s,$b)));
        }}
        foreach(["\xF0\x9F\x98\x80", "\u{2028}",str_repeat('é',600000)] as $s){self::assertTrue(Rules::same($s,$s));self::assertFalse(Rules::same($s,$s.'x'));}
    }
    public function testSmallContainersPreserveCanonicalOrderingAndDoNotHideErrors():void {
        $values=[null,true,false,0,1,-1,0.0,-0.0,1.0,INF,NAN,'','1','�',"\xFF",[],['nested'=>1],(object)['x'=>1]];
        foreach($values as $a){foreach($values as $b){
            foreach([
                [['schema'=>$a,'id'=>$b,'digest'=>'x'],['digest'=>'x','id'=>$b,'schema'=>$a]],
                [[0=>$a,1=>$b],[1=>$b,0=>$a]],
                [['first'=>$a,'second'=>$b],['first'=>$b,'second'=>$a]],
                [['01'=>$a,-1=>$b],[-1=>$b,'01'=>$a]],
                [['first'=>1,'later'=>$a],['first'=>2,'later'=>$b]],
                [["\xFF"=>$a],["\xFF"=>$b]],
                [['left'=>$a],['right'=>$b]],
            ] as [$left,$right]){
                self::assertSame($this->outcome(fn()=>StrictJson::canonical($left)===StrictJson::canonical($right)),$this->outcome(fn()=>Rules::same($left,$right)));
            }
        }}
        $resource=fopen('php://memory','r+');try{self::assertSame($this->outcome(fn()=>StrictJson::canonical(['x'=>$resource])===StrictJson::canonical(['x'=>$resource])),$this->outcome(fn()=>Rules::same(['x'=>$resource],['x'=>$resource])));}finally{fclose($resource);}
    }
    public function testObjectEffectsAndReferencedArraysKeepEncoderBehavior():void {
        $make=static fn()=>new class implements \JsonSerializable {public int $calls=0;public function jsonSerialize():mixed{return ++$this->calls;}};
        $a=$make();$b=$make();self::assertSame(StrictJson::canonical($a)===StrictJson::canonical($a),Rules::same($b,$b));self::assertSame($a->calls,$b->calls);
        $left=['z'=>2,'a'=>1];$right=$left;$a=['nested'=>&$left];$b=['nested'=>&$right];
        self::assertSame(StrictJson::canonical($a)===StrictJson::canonical($a),Rules::same($b,$b));self::assertSame($left,$right);
    }
    public function testRawIndexKeepsNumericWhitespaceUnicodeErrorsAndCopyIsolation():void {
        $cases=['0','1',(string)PHP_INT_MAX,'00','01',' 0','0 ','"0"','"00"','null','true','[]','{}','{"value":1}',' {"value":1}','{"value":2}','{"value":1,"value":1}','{"value":"é"}','{"value":"\\u00e9"}','{"value":"'."\xFF".'"}',str_repeat('[',33).'0'.str_repeat(']',33)];
        StrictJson::within(function()use($cases):void{Entry::within(function()use($cases):void{
            foreach([...$cases,...array_reverse($cases)] as $raw){self::assertSame($this->outcome(fn()=>Entry::decode($raw)),$this->outcome(fn()=>StrictJson::decode($raw)));}
            $raw='{"value":{"generation":1}}';$v=StrictJson::decode($raw);$v['value']['generation']=9;
            self::assertSame(Entry::decode($raw),StrictJson::decode($raw));
            for($i=0;$i<150;$i++){$raw='{"generation":'.$i.'}';self::assertSame(Entry::decode($raw),StrictJson::decode($raw));}
            $this->assertSame($this->outcome(fn()=>Entry::decode(str_repeat(' ',1048577))),$this->outcome(fn()=>StrictJson::decode(str_repeat(' ',1048577))));
        });});
    }
    public function testRecordSourceOrderingMatchesEntryIncludingNumericStringsAndAliases():void {
        $entry=\App\Tests\Imperium\Runtime\Support\EntryRulesComparison::class;
        $make=static function(array $sources)use($entry):array{
            $v=['schema'=>'example/v1','id'=>'record-0001','instance_id'=>'instance-0001','citadel_id'=>'citadel-0001','created_at'=>1700000000,'producer'=>['service'=>'synthetic','source_commit'=>str_repeat('a',40)],'sources'=>$sources,'body'=>['value'=>1]];
            $v['record_digest']=$entry::hash($v);return $v;
        };
        $refs=[];foreach([['1','10000000'],['01','1e000007'],['alpha','source-0001']] as [$schema,$id]){$refs[]=['schema'=>$schema,'id'=>$id,'digest'=>'sha256:'.str_repeat('a',64)];}
        $sets=[[],[$refs[0]],$refs,array_reverse($refs),[$refs[0],$refs[0]],[$refs[0]+['extra'=>1]],['named'=>$refs[0]]];
        foreach($sets as $sources){foreach([false,true] as $reverse){
            if($reverse){$sources=array_map(static fn($r)=>array_reverse($r,true),$sources);}
            $v=$make($sources);self::assertSame($this->outcome(fn()=>$entry::record($v)),$this->outcome(fn()=>Rules::record($v)));
        }}
        foreach([false,true] as $shared){
            $left=$refs[0];$right=$left;$a=$make([$left]);$b=$make([$right]);
            if($shared){$a['sources'][0]=&$left;$b['sources'][0]=&$right;}
            self::assertSame($this->outcome(fn()=>$entry::record($a)),$this->outcome(fn()=>Rules::record($b)));self::assertSame($left,$right);
        }
    }
    public function testSameRecordIdentityNeverSubstitutesOtherBytesOrValues():void {
        StrictJson::within(function():void{Entry::within(function():void{
            foreach([1,2,1] as $generation){
                $raw=json_encode(['schema'=>'example/v1','id'=>'same-record','record_digest'=>'sha256:'.str_repeat('a',64),'body'=>['generation'=>$generation]],JSON_THROW_ON_ERROR);
                $expected=Entry::decode($raw);$actual=StrictJson::decode($raw);self::assertSame($expected,$actual);
                foreach([false,true] as $unsigned){if($unsigned){unset($expected['record_digest'],$actual['record_digest']);}
                    self::assertSame(Entry::canonical($expected),StrictJson::canonical($actual));
                    $expected['body']['generation']++;$actual['body']['generation']++;
                    self::assertSame(Entry::canonical($expected),StrictJson::canonical($actual));
                }
            }
        });});
    }
}
