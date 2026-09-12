<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\{AssignmentFixture as F,AssignmentProcessContext};
use App\Imperium\Runtime\Onboarding\Assignment\ApplicationHistory;
use PHPUnit\Framework\TestCase;
final class AssignmentProcessTest extends TestCase
{
    private function wait(callable $ready):void{$end=microtime(true)+300;while(!$ready()){if(microtime(true)>$end){self::fail('Assignment worker exceeded 300 seconds');}usleep(10000);}}
    private function workers(string $root,array $modes):array
    {
        $workers=[];try{
            foreach($modes as $mode){$p=proc_open([PHP_BINARY,'-d','allow_url_fopen=0','-d','disable_functions=curl_exec,curl_multi_exec,fsockopen,pfsockopen,stream_socket_client,socket_connect',__DIR__.'/Support/assignment-worker.php',$root,$mode],
                [0=>['pipe','r'],1=>['file',$root.'/assignment-output-'.$mode,'w'],2=>['file',$root.'/assignment-error-'.$mode,'w']],$pipes);self::assertIsResource($p);fclose($pipes[0]);$workers[$mode]=$p;}
            foreach($modes as $mode){if(!in_array($mode,['snapshot','resolve'],true)){$this->wait(static fn():bool=>is_file($root.'/assignment-ready-'.$mode));}}
            file_put_contents($root.'/assignment-go','go');$out=[];
            foreach($workers as $mode=>$p){$status=null;$this->wait(function()use($p,&$status):bool{$status=proc_get_status($p);return !$status['running'];});
                self::assertSame('',file_get_contents($root.'/assignment-error-'.$mode));$out[$mode]=['exit'=>$status['exitcode'],'output'=>trim(file_get_contents($root.'/assignment-output-'.$mode))];}
            return $out;
        }finally{foreach($workers as $p){if(is_resource($p)){if(proc_get_status($p)['running']){proc_terminate($p);}proc_close($p);}}}
    }
    public function testPublicationFaultsContendingWritersAndFreshProcessSettings():void
    {
        $f=new F();try{
            $f->assessed();$d=$f->fresh->d;$root=$d->f->root;$store=$d->f->store;$q=$d->request('apply-assignments');$before=$store->journal->read();$calls=$f->requests;
            file_put_contents($root.'/assignment-worker-input.json',$d->f::json(AssignmentProcessContext::export($f,$q)));
            $out=$this->workers($root,['before-publication']);self::assertSame(73,$out['before-publication']['exit']);self::assertSame($before,$store->journal->read());
            self::assertNotEmpty(glob($root.'/var/imperium/citadel/formation/*.pending.*'));
            unlink($root.'/assignment-go');$out=$this->workers($root,['race-one','different-id']);$exits=array_column($out,'exit');sort($exits);self::assertSame([0,23],$exits);
            $outputs=array_column($out,'output');sort($outputs);self::assertSame(['O2_STALE_HEAD','STEP_ADMITTED'],$outputs);
            $frame=$store->journal->read();self::assertSame($before['generation']+1,$frame['generation']);self::assertCount(1,$frame['state']['onboarding']['applications']);
            $a=ApplicationHistory::latest($frame['state']['onboarding']);$snapshot=$f->settings()->snapshot();
            $original=$frame['state']['onboarding']['commands'][\App\Imperium\Runtime\Onboarding\Ledger\LedgerState::key('command',['instance-test',$a['command_ref']['sequence_id'],$a['command_ref']['command_id']])]['request'];
            file_put_contents($root.'/assignment-worker-input.json',$d->f::json(AssignmentProcessContext::export($f,$original)));
            $same=$this->workers($root,['same-one','same-two']);foreach($same as $result){self::assertSame(0,$result['exit']);self::assertSame('HISTORICAL_RECOGNITION',$result['output']);}self::assertSame($frame,$store->journal->read());
            $resolved=$this->workers($root,['resolve']);self::assertSame(0,$resolved['resolve']['exit']);self::assertEquals($snapshot['assignments'][0],json_decode($resolved['resolve']['output'],true,512,JSON_THROW_ON_ERROR));
            $replacement=$f->replacementRequest();$beforeChange=$store->journal->read();file_put_contents($root.'/assignment-worker-input.json',$d->f::json(AssignmentProcessContext::export($f,$replacement)));
            $out=$this->workers($root,['after-publication']);self::assertSame(74,$out['after-publication']['exit']);$after=$store->journal->read();self::assertSame($beforeChange['generation']+1,$after['generation']);self::assertCount(2,$after['state']['onboarding']['applications']);
            $restart=$this->workers($root,['snapshot']);self::assertSame(0,$restart['snapshot']['exit']);$persisted=json_decode($restart['snapshot']['output'],true,512,JSON_THROW_ON_ERROR);
            foreach($persisted['assignments'] as $tuple){self::assertSame(2,$tuple['generation']);self::assertSame('deepseek-v4-pro',$tuple['model_id']);}
            $replay=$this->workers($root,['replay']);self::assertSame('HISTORICAL_RECOGNITION',$replay['replay']['output']);self::assertSame($after,$store->journal->read());
            self::assertSame('HISTORICAL_RECOGNITION',$f->ledger()->advance($d->f::json($original))['status']);self::assertSame($after,$store->journal->read());self::assertSame($calls,$f->requests);
            $one=$f->replacementRequest(1);$two=$f->replacementRequest(2);$one['expected_head']=$two['expected_head']=$f->head();$priorRace=$store->journal->read();
            $input=AssignmentProcessContext::export($f,$one);$input['requests']=['change-one'=>$one,'change-two'=>$two];file_put_contents($root.'/assignment-worker-input.json',$d->f::json($input));
            unlink($root.'/assignment-go');$changed=$this->workers($root,['change-one','change-two']);$outputs=array_column($changed,'output');sort($outputs);self::assertSame(['ASSIGNMENTS_CHANGED','O2_STALE_HEAD'],$outputs);
            $afterRace=$store->journal->read();self::assertSame($priorRace['generation']+1,$afterRace['generation']);self::assertCount(3,$afterRace['state']['onboarding']['applications']);foreach($f->settings()->snapshot()['assignments'] as $tuple){self::assertSame(3,$tuple['generation']);}
            $resume=['schema'=>'imperium.provider-onboarding-resume/v2','sequence_id'=>$original['sequence_id'],'command_id'=>'process-recovery','expected_head'=>$f->head(),'recognize_command_ref'=>$a['command_ref']];
            file_put_contents($root.'/assignment-worker-input.json',$d->f::json(AssignmentProcessContext::export($f,$resume)));$recovered=$this->workers($root,['recover']);self::assertSame(0,$recovered['recover']['exit']);self::assertSame('EVIDENCE_RECOGNITION',$recovered['recover']['output']);
            self::assertSame($afterRace['state']['onboarding']['applications'],$store->journal->read()['state']['onboarding']['applications']);self::assertSame($calls,$f->requests);
        }finally{$f->close();}
    }
}
