<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Imperium\Runtime\Citadel\Formation\{FormationJournal,JournalCanonicalHash};
use PHPUnit\Framework\TestCase;
final class JournalFieldEncodingCompatibilityTest extends TestCase
{
    private function entry(string $raw):array {$v=json_decode($raw,true,512,JSON_THROW_ON_ERROR);$d=$v['record_digest']??null;unset($v['record_digest']);return [$v,$d,is_string($d)?FormationJournal::digest($v):null];}
    private function outcome(callable $f):array {try{return ['value',$f()];}catch(\Throwable $e){return ['error',$e::class,$e->getCode(),$e->getMessage()];}}
    private function raw(array $onboarding,array $rest=[],int $generation=1):string {return json_encode(['schema'=>'imperium.citadel-formation-frame/v1','generation'=>$generation,'previous_digest'=>null,'state'=>['onboarding'=>['evidence'=>$onboarding]]+$rest,'record_digest'=>'same-digest'],JSON_THROW_ON_ERROR);}
    public function testFullValuesGenerationsOrderingAndMarkerCollisions():void {
        $codec=new JournalCanonicalHash();$long=str_repeat('escaped " bytes \\ é / ',300);$base=['schema'=>'same','id'=>'same','generation'=>1,'raw'=>$long];
        $values=[$base,$base+['extra'=>1],array_replace($base,['generation'=>2]),array_replace($base,['raw'=>substr_replace($long,'MUTATION',100,8)]),array_reverse($base,true),$base];
        foreach($values as $i=>$value){foreach([[],['other'=>["\0"=>0]],['other'=>str_repeat('{"\\u0000":0}',100)]] as $rest){$raw=$this->raw($value,$rest,$i+1);self::assertSame($this->entry($raw),$codec->read($raw));}}
        $withMarker=$base+['marker'=>["\0"=>0]];$raw=$this->raw($withMarker);self::assertSame($this->entry($raw),$codec->read($raw));self::assertSame($this->entry($raw),$codec->read($raw));
        $raw=$this->raw($base+['float'=>0.12345678901234567]);$before=ini_get('serialize_precision');try{foreach(['-1','17','5','-1'] as $precision){ini_set('serialize_precision',$precision);self::assertSame($this->entry($raw),$codec->read($raw));}}finally{ini_set('serialize_precision',$before);}
        $raw=$this->raw($base);[$frame]=$codec->read($raw);$alias=&$frame['state']['onboarding']['evidence']['raw'];$alias='changed';self::assertSame($this->entry($raw),$codec->read($raw));
    }
    public function testNativeDecodeAndEncodingErrorPriority():void {
        $codec=new JournalCanonicalHash();$large=str_repeat('x',5000);
        $cases=['null','0','[]','{}','{"record_digest":null,"state":1e999}','{"record_digest":"x","state":1e999}','{"record_digest":"x","state":{"onboarding":{"v":1e999}}}','{"record_digest":"x","state":{"onboarding":{"v":"'.$large.'"}},"extra":1e999}','{"record_digest":"x","state":{"x":1,"x":2}}','{"record_digest":"x","state":"'."\xFF".'"}','{"record_digest":"x","state":"\\uD800"}','{"record_digest":"x","state":}'];
        foreach([63,64,65,127,509,510,511,512] as $depth){$cases[]='{"record_digest":"x","state":{"onboarding":{"raw":"'.$large.'","deep":'.str_repeat('[',$depth).'0'.str_repeat(']',$depth).'}}}';}
        foreach($cases as $raw){self::assertSame($this->outcome(fn()=>$this->entry($raw)),$this->outcome(fn()=>$codec->read($raw)));}
    }
    public function testSignedZeroMustNeverShareCanonicalEncoding():void {
        $codec=new JournalCanonicalHash();$raw=$this->raw(['raw'=>str_repeat('x',5000),'float'=>1.5]);
        foreach(['-0.0','0.0','-0.0'] as $number){$input=str_replace('1.5',$number,$raw);self::assertSame($this->entry($input),$codec->read($input));}
    }
    public function testSharedBudgetsReplacementAndAllMeasuredFields():void {
        $codec=new JournalCanonicalHash();
        foreach([4200000,10000,5000] as $size){
            $state=['evidence'=>['raw'=>str_repeat('a',$size)],'acts'=>['raw'=>str_repeat('b',4200000)],'policies'=>['raw'=>str_repeat('p',5000)],'augur_migration'=>['raw'=>str_repeat('m',5000)],'assignment_migration'=>['raw'=>str_repeat('n',5000)]];
            $raw=json_encode(['record_digest'=>'same','state'=>['onboarding'=>$state]],JSON_THROW_ON_ERROR);self::assertSame($this->entry($raw),$codec->read($raw));
            $entries=(new \ReflectionProperty($codec,'entries'))->getValue($codec);self::assertLessThanOrEqual(5,count($entries));
            foreach(['nodes'=>65536,'rawBytes'=>8388608,'encodedBytes'=>8388608] as $property=>$limit){$actual=(new \ReflectionProperty($codec,$property))->getValue($codec);self::assertLessThanOrEqual($limit,$actual);self::assertSame(array_sum(array_column($entries,$property==='encodedBytes'?'size':$property)),$actual);}
        }
        self::assertCount(5,(new \ReflectionProperty($codec,'entries'))->getValue($codec));
    }
    public function testBoundsAndExceptionLifetime():void {
        $codec=new JournalCanonicalHash();$raw=$this->raw(['raw'=>str_repeat('x',5000)]);self::assertSame($this->entry($raw),$codec->read($raw));
        $retained=(new \ReflectionProperty($codec,'entries'))->getValue($codec);self::assertIsArray($retained);
        foreach([['raw'=>str_repeat('x',8388609)],['many'=>array_fill(0,65537,1)],['small'=>1]] as $large){$raw=$this->raw($large);self::assertSame($this->entry($raw),$codec->read($raw));self::assertSame($retained,(new \ReflectionProperty($codec,'entries'))->getValue($codec));}
        self::assertLessThanOrEqual(8388608,(new \ReflectionProperty($codec,'encodedBytes'))->getValue($codec));
        try{$codec->read('{');self::fail('Malformed JSON must throw');}catch(\JsonException){}
        self::assertSame([],(new \ReflectionProperty($codec,'entries'))->getValue($codec));self::assertSame(0,(new \ReflectionProperty($codec,'encodedBytes'))->getValue($codec));
        $fresh=new JournalCanonicalHash();self::assertSame([],(new \ReflectionProperty($fresh,'entries'))->getValue($fresh));
    }
}
