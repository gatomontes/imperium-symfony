<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules;
use App\Tests\Imperium\Runtime\Support\EntryRulesComparison as Entry;
use PHPUnit\Framework\TestCase;
final class PureReferenceKeyCompatibilityTest extends TestCase
{
    private function outcome(callable $call):array {try{return ['value',$call()];}catch(\Throwable $e){return ['error',$e::class,$e->getCode(),$e->getMessage()];}}
    public function testReferenceBytesValidationPriorityAndAliases():void {
        $valid=['schema'=>'test/v1','id'=>'source-001','digest'=>'sha256:'.str_repeat('a',64)];
        $values=[null,0,1,false,true,1.0,[],(object)['x'=>1],'',' ','0','1','test/v1','é / 漢字',"\0\n\t",'"\\',"\xFF",'source-001','sha256:'.str_repeat('a',64),str_repeat('x',65537)];
        foreach(array_keys($valid) as $field){foreach($values as $value){$ref=$valid;$ref[$field]=$value;foreach([$ref,array_reverse($ref,true)] as $v){self::assertSame($this->outcome(fn()=>Entry::key($v)),$this->outcome(fn()=>Rules::key($v)));}}}
        foreach([null,[],array_values($valid),$valid+['extra'=>1],['schema'=>''],['schema'=>"\xFF",'id'=>0,'digest'=>null]] as $v){self::assertSame($this->outcome(fn()=>Entry::key($v)),$this->outcome(fn()=>Rules::key($v)));}
        foreach(['schema','id','digest'] as $field){$a=$valid;$b=$valid;$left=$a[$field];$right=$b[$field];$a[$field]=&$left;$b[$field]=&$right;self::assertSame(Entry::key($a),Rules::key($b));self::assertSame($a,$b);self::assertSame($left,$right);}
        foreach(['1','01','1e2','é/漢字',"\u{2028}","\0",'"\\'] as $schema){$v=$valid;$v['schema']=$schema;self::assertSame($this->outcome(fn()=>Entry::key($v)),$this->outcome(fn()=>Rules::key($v)));}
    }
}
