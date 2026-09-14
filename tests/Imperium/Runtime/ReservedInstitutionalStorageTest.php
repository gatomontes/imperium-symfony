<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Imperium\Runtime\Persistence\{AtomicTransition,ImmutableRecordStore as Immutable,MutableStateStore as Mutable};
use App\Imperium\Runtime\Citadel\Formation\{FormationJournal as Journal,FormationOwnerFrame as Owner};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
final class ReservedInstitutionalStorageTest extends TestCase
{
    private string $root;
    protected function setUp():void { $this->root=sys_get_temp_dir().'/ppc4-storage-'.bin2hex(random_bytes(10));mkdir($this->root); }
    protected function tearDown():void {
        foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root,\FilesystemIterator::SKIP_DOTS),\RecursiveIteratorIterator::CHILD_FIRST) as $p) {
            if($p->isLink()){unlink($p->getPathname());}elseif($p->isDir()){rmdir($p->getPathname());}else{unlink($p->getPathname());}
        }rmdir($this->root);
    }
    public static function targets():array { return [
        ['var/imperium','bootstrap-state'],['var/imperium/operator-root/installations','record-one'],
        ['var/imperium/operator-root/packages','record-one'],['var/imperium/native-authority','record-one'],
        ['var/imperium/citadel/formation','record-one'],['var/imperium/offices/garrison/occupancy','record-one'],
        ['var/imperium/offices/unknown-office/occupancy/nested','record-one'],
        ['var//imperium///native-authority/','record-one'],['var/imperium/offices//unknown/occupancy/','record-one']]; }
    public static function bare():iterable {foreach(self::targets() as $i=>[$d,$id])foreach(['put','cas','guarded'] as $m)yield "$i-$m"=>[$m,$d,$id];}
    #[DataProvider('bare')]
    public function testOldEntriesRefuseReservedTargetsBeforeAnyEffects(string $method,string $directory,string $id):void {
        $a=new AtomicTransition($this->root);$called=false;
        try {
            if($method==='put'){(new Immutable($this->root,$a))->put($directory,$id,[]);}
            elseif($method==='cas'){(new Mutable($this->root,$a))->compareAndSwap($directory.'/'.$id.'.json',null,[]);}
            else{(new Mutable($this->root,$a))->compareAndSwapGuarded($directory.'/'.$id.'.json',null,function()use(&$called){$called=true;},[]);}
            self::fail('Reserved bare write accepted');
        }catch(\RuntimeException $e){self::assertSame('PPC401_RESERVED_STORAGE_OWNER_REQUIRED',$e->getMessage());}
        self::assertFalse($called);self::assertSame(['.','..'],scandir($this->root));
    }
    public static function mutableAliases():iterable {
        yield ['var/./imperium/native-authority/./new.json',true];
        yield ['var/imperium/offices/unknown/./occupancy/new.json',true];
        yield ['var/imperium/Native-Authority/new.json',PHP_OS_FAMILY==='Windows'];
        if(PHP_OS_FAMILY==='Windows')yield ['var./imperium/native-authority/new.json',true];
        yield ['var/imperium/native-authority-sibling/new.json',false];
        yield ['var/imperium/offices/unknown/occupancy-sibling/new.json',false];
        yield ['var/imperium/offices/unknown/custody/new.json',false];
        yield ['var/imperium/bootstrap-state.json-sibling.json',false];
    }
    #[DataProvider('mutableAliases')]
    public function testValidatorAliasesAndOrdinarySiblings(string $path,bool $reserved):void {
        $m=new Mutable($this->root,new AtomicTransition($this->root));
        if($reserved){$this->expectExceptionMessage('PPC401_RESERVED_STORAGE_OWNER_REQUIRED');}
        $r=$m->compareAndSwap($path,null,['value'=>1]);self::assertSame(1,$r['value']);
    }
    public static function owned():iterable {foreach(self::targets() as $i=>$target)yield (string)$i=>$target;}
    #[DataProvider('owned')]
    public function testEveryClassHasExplicitOwnedImmutableAndCasBehavior(string $directory,string $id):void {
        Owner::run($this->root,function(Owner $o)use($directory,$id):void{
            $a=new AtomicTransition($this->root);$i=new Immutable($this->root,$a);$m=new Mutable($this->root,$a);
            $r=$i->putInOwner($o,$directory,$id,['value'=>1]);self::assertSame($r,$i->putInOwner($o,$directory,$id,['value'=>1]));
            $count=0;$next=$m->compareAndSwapGuardedInOwner($o,$directory.'/'.$id.'.json',$r['record_digest'],function()use(&$count){$count++;},['value'=>2]);
            self::assertSame(1,$count);self::assertSame(2,$next['value']);
            $last=$m->compareAndSwapInOwner($o,$directory.'/'.$id.'.json',$next['record_digest'],['value'=>3]);self::assertSame(3,$last['value']);
        });
    }
    public function testWrongAndExpiredOwnersRefuseBeforeTargetEffects():void {
        $escaped=null;Owner::run($this->root,function(Owner $o)use(&$escaped){$escaped=$o;});
        $foreign=$this->root.'/foreign';mkdir($foreign);$a=new AtomicTransition($foreign);
        Owner::run($this->root,function(Owner $o)use($foreign,$a):void{
            foreach(['put','cas','guarded']as$method){try{$this->writeOwned($method,$o,$foreign,$a);self::fail('Wrong owner');}catch(\RuntimeException $e){self::assertSame('PPC302_LIVE_SAME_ROOT_OWNER_REQUIRED',$e->getMessage());}}
        });
        foreach(['put','cas','guarded']as$method){try{$this->writeOwned($method,$escaped,$this->root,new AtomicTransition($this->root));self::fail('Expired owner');}catch(\RuntimeException $e){self::assertSame('PPC302_LIVE_SAME_ROOT_OWNER_REQUIRED',$e->getMessage());}}
        self::assertSame(['.','..'],scandir($foreign));self::assertDirectoryDoesNotExist($this->root.'/var/imperium/native-authority');
    }
    private function writeOwned(string $method,Owner $o,string $root,AtomicTransition $a):void {
        if($method==='put'){(new Immutable($root,$a))->putInOwner($o,'var/imperium/native-authority','new',[]);}
        elseif($method==='cas'){(new Mutable($root,$a))->compareAndSwapInOwner($o,'var/imperium/native-authority/new.json',null,[]);}
        else{(new Mutable($root,$a))->compareAndSwapGuardedInOwner($o,'var/imperium/native-authority/new.json',null,static function(){self::fail('Invalid owner guard ran');},[]);}
    }
    public function testOrdinaryReplayConflictCasGuardOrderAndAbsentRoot():void {
        $root=$this->root.'/absent';$a=new AtomicTransition($root);$i=new Immutable($root,$a);$m=new Mutable($root,$a);
        $r=$i->put('var/imperium/mission/ordinary','original',['value'=>1]);self::assertSame($r,$i->put('var/imperium/mission/ordinary','original',['value'=>1]));
        try{$i->put('var/imperium/mission/ordinary','original',['value'=>2]);self::fail('Conflict accepted');}catch(\RuntimeException $e){self::assertSame('PST111_IMMUTABLE_RECORD_CONFLICT',$e->getMessage());}
        $path='var/imperium/mission/ordinary/original.json';$calls=0;
        try{$m->compareAndSwapGuarded($path,'wrong',function()use(&$calls){$calls++;},[]);self::fail('CAS conflict accepted');}catch(\RuntimeException $e){self::assertSame('PST121_MUTABLE_STATE_COMPARE_AND_SWAP_CONFLICT',$e->getMessage());}self::assertSame(0,$calls);
        try{$m->compareAndSwapGuarded($path,$r['record_digest'],function()use(&$calls){$calls++;throw new \RuntimeException('CONTROLLED');},[]);self::fail('Guard failure accepted');}catch(\RuntimeException $e){self::assertSame('CONTROLLED',$e->getMessage());}
        self::assertSame(1,$calls);self::assertSame($r,$m->read($path));
        $r2=$m->compareAndSwap($path,$r['record_digest'],['value'=>2]);self::assertSame(2,$r2['value']);
        file_put_contents($root.'/'.$path,'{}');try{$i->read('var/imperium/mission/ordinary','original');self::fail('Corruption accepted');}catch(\RuntimeException $e){self::assertSame('PST113_IMMUTABLE_RECORD_TAMPERED',$e->getMessage());}
    }
    public function testSymlinkAliasesAndDanglingTargetsRefuse():void {
        $target=$this->root.'/var/imperium/native-authority';mkdir($target,0770,true);$link=$this->root.'/alias';
        if(!@symlink($target,$link)){self::markTestSkipped('Host cannot create symlinks; suitable-host alias proof required');}
        foreach(['put','cas','guarded']as$method){try{
            $a=new AtomicTransition($this->root);
            if($method==='put'){(new Immutable($this->root,$a))->put('alias','new',[]);}elseif($method==='cas'){(new Mutable($this->root,$a))->compareAndSwap('alias/new.json',null,[]);}else{(new Mutable($this->root,$a))->compareAndSwapGuarded('alias/new.json',null,static function(){self::fail('Alias guard ran');},[]);}
            self::fail('Alias accepted');
        }catch(\RuntimeException $e){self::assertSame('PPC402_STORAGE_TOPOLOGY_AMBIGUOUS',$e->getMessage());}}
        unlink($link);self::assertTrue(symlink($this->root.'/missing',$link));
        $this->expectExceptionMessage('PPC402_STORAGE_TOPOLOGY_AMBIGUOUS');(new Mutable($this->root,new AtomicTransition($this->root)))->compareAndSwap('alias/new.json',null,[]);
    }
    public function testImmutableBootstrapIdCaseAndOrdinaryTrailingSeparator():void {
        $i=new Immutable($this->root,new AtomicTransition($this->root));
        if(PHP_OS_FAMILY==='Windows'){
            try{$i->put('var/imperium','Bootstrap-State',[]);self::fail('Case alias accepted');}catch(\RuntimeException $e){self::assertSame('PPC401_RESERVED_STORAGE_OWNER_REQUIRED',$e->getMessage());}
            self::assertSame(['.','..'],scandir($this->root));
        }else{self::assertArrayHasKey('record_digest',$i->put('var/imperium','Bootstrap-State',[]));}
        $r=$i->put('var//imperium/mission/ordinary/','new',['v'=>1]);self::assertSame($r,$i->read('var/imperium/mission/ordinary','new'));
    }
    public function testAbsentOrdinaryParentsAreWarningFreeWithStrictErrorHandler():void {
        set_error_handler(static function(int $severity,string $message):never {throw new \ErrorException($message,0,$severity);});
        try {
            $a=new AtomicTransition($this->root);$m=new Mutable($this->root,$a);$calls=0;
            self::assertArrayHasKey('record_digest',(new Immutable($this->root,$a))->put('ordinary/absent/immutable','new',[]));
            self::assertArrayHasKey('record_digest',$m->compareAndSwap('ordinary/absent/cas/new.json',null,[]));
            self::assertArrayHasKey('record_digest',$m->compareAndSwapGuarded('ordinary/absent/guarded/new.json',null,function()use(&$calls){$calls++;},[]));
            self::assertSame(1,$calls);
        }finally{restore_error_handler();}
    }
    public function testInvalidGrammarIsNotBroadened():void {
        foreach(['var/../bad.json','/var/imperium/native-authority/new.json']as$p){try{(new Mutable($this->root,new AtomicTransition($this->root)))->compareAndSwap($p,null,[]);self::fail('Invalid grammar');}catch(\InvalidArgumentException $e){self::assertSame('PST120_MUTABLE_STATE_PATH_INVALID',$e->getMessage());}}
        self::assertSame(['.','..'],scandir($this->root));
    }
}
