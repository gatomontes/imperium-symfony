<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Tests\Imperium\Runtime\Support\ProtectedMissionFixture;
use PHPUnit\Framework\TestCase;

final class ProtectedMissionAuthorityBatch2Test extends TestCase
{
    public function testVerifiedConsumptionRestartReplayRequiredStateAndTerminalReentry(): void
    {
        $f=new ProtectedMissionFixture(); $caps=$f->call('issue',['authorization_id'=>$f->id])['capabilities'];
        $this->refuses($f,'PMA_REQUIRED_STATE','consume',['capability'=>$caps[1]]);
        $first=$f->call('consume',['capability'=>$caps[0]]);
        self::assertSame('ADMITTED',$first['state']); self::assertCount(1,$first['history']);
        $this->refuses($f,'PMA_REPLAY','consume',['capability'=>$caps[0]]);
        $fresh=$f->call('issue',['authorization_id'=>$f->id])['capabilities'];
        self::assertSame('INSPECTING',$f->call('consume',['capability'=>$caps[1]])['state']);
        self::assertSame('COMPLETED',$f->call('consume',['capability'=>$caps[2]])['state']);
        $this->refuses($f,'PMA_TERMINAL','consume',['capability'=>$fresh[0]]);
        $this->refuses($f,'PMA_TERMINAL','issue',['authorization_id'=>$f->id]);
        self::assertCount(3,$f->call('status',['authorization_id'=>$f->id])['lifecycle']['history']);
    }
    public function testForgedDtoBindingsAndDirectWriterCannotCreateAuthority(): void
    {
        $f=new ProtectedMissionFixture(); $cap=$f->call('issue',['authorization_id'=>$f->id])['capabilities'][0];
        foreach (['actor'=>'attacker','mission_id'=>'different','target'=>['commit'=>'wrong'],'issuer'=>'wrong','nonce'=>str_repeat('a',48),'to'=>'COMPLETED'] as $key=>$value) {
            $forged=$cap; $forged['payload'][$key]=$value;
            $this->refuses($f,'PMA_CAPABILITY_INVALID','consume',['capability'=>$forged]);
        }
        $this->refuses($f,'PMA_AUTHORITY_ABSENT','consume',['capability'=>['payload'=>['authorization_id'=>'fabricated']]]);
        self::assertSame('AUTHORIZED',$f->call('status',['authorization_id'=>$f->id])['lifecycle']['state']);
        $this->refuses($f,'PMA_OPERATION_REFUSED','write',['record'=>['state'=>'COMPLETED']]);
    }
    public function testRevocationSupersessionTrustAndExpiryAtUseRefuse(): void
    {
        foreach (['revoke','supersede','cancel','revoke-trust'] as $action) {
            $f=new ProtectedMissionFixture(); $cap=$f->call('issue',['authorization_id'=>$f->id])['capabilities'][0];
            $f->call('control',$f->control($action));
            $this->refuses($f,$action==='revoke-trust'?'PMA_SIGNATURE_OR_TRUST_INVALID':'PMA_AUTHORITY_INACTIVE','consume',['capability'=>$cap]);
            self::assertCount(0,$f->call('status',['authorization_id'=>$f->id])['lifecycle']['history']);
        }
        $f=new ProtectedMissionFixture(); $f->state['authorizations'][$f->id]['payload']['expires_at']=time()-1;
        $f->state['authorizations'][$f->id]['signature']=$f->sign($f->state['authorizations'][$f->id]['payload']); $f->save();
        $this->refuses($f,'PMA_AUTHORITY_INACTIVE','issue',['authorization_id'=>$f->id]);
        self::assertStringNotContainsString('issuer_secret',file_get_contents($f->root.'/authority.journal'));
    }
    public function testIncompleteJournalFrameDoesNotPublishAndCompleteCorruptionRefuses(): void
    {
        $f=new ProtectedMissionFixture(); $caps=$f->call('issue',['authorization_id'=>$f->id])['capabilities'];
        file_put_contents($f->root.'/authority.journal','100 '.str_repeat('a',64)."\npartial",FILE_APPEND);
        self::assertSame('ADMITTED',$f->call('consume',['capability'=>$caps[0]])['state']);
        $this->refuses($f,'PMA_REPLAY','consume',['capability'=>$caps[0]]);
        file_put_contents($f->root.'/authority.journal','2 '.str_repeat('a',64)."\n{}",FILE_APPEND);
        $this->refuses($f,'PMA_JOURNAL_CORRUPT','consume',['capability'=>$caps[1]]);
    }
    public function testIndependentProcessesContendOnConsumptionAndRevocation(): void
    {
        foreach (['consume','revoke','supersede'] as $contender) {
            $f=new ProtectedMissionFixture(); $cap=$f->call('issue',['authorization_id'=>$f->id])['capabilities'][0];
            $request=['operation'=>'consume','arguments'=>['capability'=>$cap]];
            $other=$contender==='consume'?$request:['operation'=>'control','arguments'=>$f->control($contender)];
            $gate=$f->root.'/start'; $workers=[];
            foreach ([$request,$other] as $i=>$input) {
                $workers[$i]=new \App\Tests\Imperium\Runtime\Support\ConsumerProcess([PHP_BINARY,__DIR__.'/Support/protected_mission_worker.php',$f->root,base64_encode(json_encode($input,JSON_THROW_ON_ERROR)),$gate],$f->root,'race-'.$i);
                $workers[$i]->start();
            }
            file_put_contents($gate,'go'); $out=[];
            foreach ($workers as $worker) {self::assertSame(0,$worker->wait());self::assertSame('',$worker->getErrorOutput());$out[]=json_decode($worker->getOutput(),true,512,JSON_THROW_ON_ERROR);}
            $status=$f->call('status',['authorization_id'=>$f->id]);
            if ($contender==='consume') {
                self::assertSame(1,count(array_filter($out,fn($r)=>$r['ok'])));
                self::assertSame(['PMA_REPLAY'],array_values(array_column(array_filter($out,fn($r)=>!$r['ok']),'error')));
                self::assertCount(1,$status['lifecycle']['history']);
            } else {
                self::assertTrue($out[1]['ok']); self::assertSame($contender,$status['inactive']);
                self::assertCount($out[0]['ok']?1:0,$status['lifecycle']['history']);
                if (!$out[0]['ok']) self::assertSame('PMA_AUTHORITY_INACTIVE',$out[0]['error']);
                $this->refuses($f,'PMA_AUTHORITY_INACTIVE','consume',['capability'=>$cap]);
            }
        }
    }
    private function refuses(ProtectedMissionFixture $f,string $expected,string $operation,array $arguments): void
    {
        $before=hash_file('sha256',$f->root.'/authority.journal');
        try {$f->call($operation,$arguments);self::fail('Expected '.$expected);} catch (\RuntimeException $e) {self::assertSame($expected,$e->getMessage());}
        self::assertSame($before,hash_file('sha256',$f->root.'/authority.journal'));
    }
}
