<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Imperium\Runtime\Citadel\Formation\{FormationJournal,JournalCanonicalHash};
use PHPUnit\Framework\TestCase;
final class JournalFieldParsingCompatibilityTest extends TestCase
{
    private function outcome(callable $f):array {try{return ['value',$f()];}catch(\Throwable $e){return ['error',$e::class,$e->getCode(),$e->getMessage()];}}
    private function original(string $raw):array {$f=json_decode($raw,true,512,JSON_THROW_ON_ERROR);$d=$f['record_digest']??null;unset($f['record_digest']);return [$f,$d,is_string($d)?FormationJournal::digest($f):null];}
    public function testExactParsingWithDuplicateMembersDepthErrorsAndMalformedBytes():void
    {
        $value=['raw'=>str_repeat('quotes " slash \\ unicode é /',250),'keys'=>['00'=>'zero',0=>'integer']];
        $body=json_encode($value,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);
        $fragment='"evidence":'.$body;
        $warm='{"record_digest":"same","state":{"onboarding":{'.$fragment.'}}}';
        $cases=[$warm,'{"record_digest":"same",'.$fragment.'}',
            '{"record_digest":"same",'.$fragment.',"evidence":0}',
            '{"record_digest":"same","escaped\\"evidence":'.$body.'}',
            '{"record_digest":"same","marker":{"\\u0000":0},'.$fragment.'}',
            '{"record_digest":"same","literal":"{\\"\\\\u0000\\":0}",'.$fragment.'}',
            '{"record_digest":"same",'.$fragment.',"bad":}',
            '{"record_digest":"same",'.$fragment.',"bad":"'."\xFF".'"}',
            '{"record_digest":"same",'.$fragment.',"bad":"\\uD800"}',
            '{"record_digest":"same",'.$fragment.',"bad":1e999}',
            '{"record_digest":"same",'.$fragment.',"bad":"'."\x01".'"}',
            str_replace('"same"','null',$warm),str_replace('"same"','42',$warm),
            str_replace('quotes','changed',$warm),str_replace('"evidence":','"evidence" : ',$warm),
            str_replace('"evidence":','"evide\\u006ece":',$warm)];
        foreach([0,1,63,64,127,128,129,250,507,508,509,510,511,512,513] as $depth){
            $deep=str_repeat('[',$depth).'{'.$fragment.'}'.str_repeat(']',$depth);
            $cases[]='{"record_digest":"same","deep":'.$deep.'}';
            $cases[]='{"record_digest":"same","deep":'.$deep.',"deep":0}';
        }
        $cases[]='{"record_digest":"same","many":['.implode(',',array_fill(0,65,'{'.$fragment.'}')).']}';
        $cases[]='{"record_digest":"same","many":['.implode(',',array_fill(0,65537,'[]')).'],'.$fragment.'}';
        $cases[]='{"record_digest":"same","huge":"'.str_repeat('x',16777217).'",'.$fragment.'}';
        foreach($cases as $i=>$raw){$codec=new JournalCanonicalHash();$codec->read($warm);self::assertSame($this->outcome(fn()=>$this->original($raw)),$this->outcome(fn()=>$codec->read($raw)),'case '.$i);}
    }
    public function testParsingReuseRetainsPrivateSnapshotsAndClearsBoundedRepresentations():void
    {
        $codec=new JournalCanonicalHash();$raw=json_encode(['record_digest'=>'same','state'=>['onboarding'=>['evidence'=>['raw'=>str_repeat('x',5000)]]]],JSON_THROW_ON_ERROR);
        $codec->read($raw);[$frame]=$codec->read($raw);$frame['state']['onboarding']['evidence']['raw']='changed';
        self::assertSame($this->original($raw),$codec->read($raw));
        $entries=(new \ReflectionProperty($codec,'entries'))->getValue($codec);
        self::assertSame(array_sum(array_column($entries,'fragmentSize')),(new \ReflectionProperty($codec,'fragmentBytes'))->getValue($codec));
        self::assertLessThanOrEqual(8388608,(new \ReflectionProperty($codec,'fragmentBytes'))->getValue($codec));
        try{$codec->read('{');self::fail();}catch(\JsonException){}
        self::assertSame(0,(new \ReflectionProperty($codec,'fragmentBytes'))->getValue($codec));
        self::assertSame([],(new \ReflectionProperty($codec,'entries'))->getValue($codec));
    }
}
