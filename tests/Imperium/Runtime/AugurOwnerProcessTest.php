<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\AugurFreshFixture as F;
use App\Imperium\Runtime\Onboarding\Augur\{AugurMigration,Holder};
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore,Rules as R};
use App\Imperium\Runtime\Onboarding\Ledger\CommandLedger;
use PHPUnit\Framework\TestCase;
final class AugurOwnerProcessTest extends TestCase
{
    private function until(callable $ready):void{$end=microtime(true)+120;while(!$ready()){if(microtime(true)>$end){self::fail('Owner process barrier timeout');}usleep(10000);}}
    public function testNativeContenderWaitsForFreshPublicationAndRestartCannotReopen():void
    {
        $f=new F();$process=null;try{$d=$f->d;$root=$d->f->root;$store=$d->f->store;(new AugurMigration($store))->migrate($d->f->head());$f->ready();$d->advance('select-base');$d->advance('map-base');
            $ledger=new CommandLedger($store,$d->adapter,$d->adapter,$f->founding());$q=$d->request('found-augur');$head=$d->f->head();
            $f->baseHook=static function():void{throw new \RuntimeException('SYNTHETIC_BEFORE_PUBLICATION');};
            try{$ledger->advance($d->f::json($q));self::fail('Injected prepublication failure');}catch(\RuntimeException $e){self::assertSame('SYNTHETIC_BEFORE_PUBLICATION',$e->getMessage());}
            self::assertSame($head,$d->f->head());self::assertSame([],$store->journal->read()['state']['onboarding']['bindings']);
            $process=proc_open([PHP_BINARY,'-d','allow_url_fopen=0',__DIR__.'/Support/augur-native-worker.php',$root],[0=>['pipe','r'],1=>['file',$root.'/native-output','w'],2=>['file',$root.'/native-error','w']],$pipes);self::assertIsResource($process);fclose($pipes[0]);$this->until(static fn():bool=>is_file($root.'/native-ready'));
            $f->baseHook=function()use($f,$root):void{$f->baseHook=null;file_put_contents($root.'/native-go','go');$this->until(static fn():bool=>is_file($root.'/native-attempted'));usleep(100000);clearstatcache();self::assertSame(0,filesize($root.'/native-output'));};
            $result=$ledger->advance($d->f::json($q));$status=null;$this->until(function()use($process,&$status):bool{$status=proc_get_status($process);return !$status['running'];});self::assertSame(23,$status['exitcode']);self::assertSame("B225_FRESH_ROOT_OWNED\n",file_get_contents($root.'/native-output'));self::assertSame('',file_get_contents($root.'/native-error'));
            $reopened=new AuthorityStore($root,$store->clock,$store->instance,$store->citadel,$store->operator,$store->sourceCommit);$s=$reopened->state($reopened->journal->read()['state']);$holder=array_values($s['bindings'])[0]['record'];self::assertSame($holder,Holder::current($reopened,$s,R::reference($holder)));
            $after=$d->f->head();self::assertSame('HISTORICAL_RECOGNITION',(new CommandLedger($reopened,$d->adapter,$d->adapter,$f->founding()))->advance($d->f::json($q))['status']);self::assertSame($after,$d->f->head());
        }finally{if(is_resource($process)){if(proc_get_status($process)['running']){proc_terminate($process);}proc_close($process);}$f->close();}
    }
}
