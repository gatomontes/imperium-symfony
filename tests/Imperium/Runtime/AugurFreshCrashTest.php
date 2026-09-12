<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\AugurFreshFixture as F;
use App\Imperium\Runtime\Onboarding\Augur\AugurMigration;
use App\Imperium\Runtime\Onboarding\Ledger\CommandLedger;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules as R;
use PHPUnit\Framework\{TestCase,Attributes\DataProvider};
final class AugurFreshCrashTest extends TestCase
{
    private function wait(callable $ready):void{$end=microtime(true)+120;while(!$ready()){if(microtime(true)>$end){self::fail('FRESH worker timeout');}usleep(10000);}}
    #[DataProvider('modes')]
    public function testOriginalPublicationSurvivesProcessBoundary(string $mode):void
    {
        $f=new F();$workers=[];try{$d=$f->d;$root=$d->f->root;$store=$d->f->store;(new AugurMigration($store))->migrate($d->f->head());$f->ready();$d->advance('select-base');$d->advance('map-base');$head=$d->f->head();$q=$d->request('found-augur');
            $pins=[];foreach($d->f->sources as $key=>$h){if(in_array($h['body']['kind']??'', ['augur-base-facts','augur-base-observation','synthetic-provider-bytes'],true)){$pins[$key]=$h;}}
            file_put_contents($root.'/fresh-worker-input.json',$d->f::json(['now'=>$d->f->now,'request'=>$q,'constitution'=>$f->constitution,'artifacts'=>$f->constitutionalOriginals,'base_pins'=>$pins]));
            $modes=$mode==='race'?['contender-one','contender-two']:[$mode];
            foreach($modes as $m){$p=proc_open([PHP_BINARY,'-d','allow_url_fopen=0','-d','disable_functions=curl_exec,curl_multi_exec,fsockopen,pfsockopen,stream_socket_client,socket_connect',__DIR__.'/Support/augur-fresh-worker.php',$root,$m],[0=>['pipe','r'],1=>['file',$root.'/fresh-output-'.$m,'w'],2=>['file',$root.'/fresh-error-'.$m,'w']],$pipes);self::assertIsResource($p);fclose($pipes[0]);$workers[$m]=$p;}
            foreach($modes as $m){$this->wait(static fn():bool=>is_file($root.'/fresh-ready-'.$m));}file_put_contents($root.'/fresh-go','go');$codes=[];
            foreach($workers as $m=>$p){$status=null;$this->wait(function()use($p,&$status):bool{$status=proc_get_status($p);return !$status['running'];});$codes[]=$status['exitcode'];self::assertSame('',file_get_contents($root.'/fresh-error-'.$m));}
            $s=$store->state($store->journal->read()['state']);
            if($mode==='before-publication'){self::assertSame([73],$codes);self::assertSame($head,$d->f->head());self::assertSame([],$s['bindings']);}
            else{self::assertSame($mode==='race'?[0,0]:[74],$codes);self::assertCount(1,$s['bindings']);self::assertSame($head['generation']+1,$d->f->head()['generation']);$after=$d->f->head();self::assertSame('HISTORICAL_RECOGNITION',(new CommandLedger($store))->advance($d->f::json($q))['status']);self::assertSame($after,$d->f->head());
                if($mode==='race'){$outputs=array_map(static fn(string $m):string=>trim(file_get_contents($root.'/fresh-output-'.$m)),$modes);sort($outputs);self::assertSame(['HISTORICAL_RECOGNITION','STEP_ADMITTED'],$outputs);}
            }
        }finally{foreach($workers as $p){if(is_resource($p)){if(proc_get_status($p)['running']){proc_terminate($p);}proc_close($p);}}$f->close();}
    }
    public static function modes():iterable{yield ['before-publication'];yield ['after-publication'];yield ['race'];}
}
