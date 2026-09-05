<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\ProtectedMission\{OfflineGitInspector,PublicTrust};
use App\Tests\Imperium\Runtime\Support\ProtectedMissionFixture;
use PHPUnit\Framework\TestCase;

final class ProtectedMissionAuthorityBatch4Test extends TestCase
{
    public function testDisposableGitMissionThroughRealCliHasProgressExactBytesAndReceiptWithoutTargetMutation():void
    {
        $root=sys_get_temp_dir().'/imperium-protected-mission-'.bin2hex(random_bytes(12));mkdir($root,0700,true);
        $repo=$root.'/target';mkdir($repo);mkdir($root.'/empty-hooks');
        $this->git($repo,['init']);file_put_contents($repo.'/evidence.txt',"Committed test-only bytes.\n");
        $this->git($repo,['add','evidence.txt']);$this->git($repo,['-c','user.name=Disposable','-c','user.email=disposable@example.invalid','-c','commit.gpgsign=false','commit','-m','Disposable inspection target']);
        $commit=trim($this->git($repo,['rev-parse','HEAD']));$tree=trim($this->git($repo,['rev-parse','HEAD^{tree}']));$blob=trim($this->git($repo,['rev-parse','HEAD:evidence.txt']));
        file_put_contents($repo.'/evidence.txt',"Uncommitted substitution must not be inspected.\n");
        // These hostile configuration values are inert because the reader never invokes Git.
        file_put_contents($repo.'/.git/config',"\n[remote \"origin\"]\n url = https://invalid.example/no-fetch\n promisor = true\n[core]\n hooksPath = hostile-hooks\n fsmonitor = malicious-command\n",FILE_APPEND);
        $before=$this->hashes($repo);
        $pair=sodium_crypto_sign_keypair();$public=sodium_crypto_sign_publickey($pair);
        $trust=['identity'=>'disposable-mission-operator','competence'=>PublicTrust::COMPETENCE,'public_key'=>base64_encode($public),'not_before'=>time()-1,'expires_at'=>time()+600];
        $this->cli($root,['enroll',hash('sha256',$public)],$trust);
        $input=ProtectedMissionFixture::input();$input['mission']['target']=['repository'=>$repo,'commit'=>$commit,'tree'=>$tree];
        $cid=$this->cli($root,['prepare'],$input)['challenge_id'];
        $payload=$this->cli($root,['export',$cid]);
        $signature=base64_encode(sodium_crypto_sign_detached(CanonicalJson::encode($payload),sodium_crypto_sign_secretkey($pair)));
        $this->cli($root,['submit'],['challenge_id'=>$cid,'signature'=>$signature]);
        $id=$this->cli($root,['derive',$cid])['authorization_id'];
        $this->cli($root,['verify',$id]);
        $issued=$this->cli($root,['request'],['operation'=>'issue','arguments'=>['authorization_id'=>$id]]);
        $progress=[];
        foreach ($issued['capabilities'] as $cap) {
            $record=$this->cli($root,['request'],['operation'=>'consume','arguments'=>['capability'=>$cap]]);
            $progress[]=$record['state'];
            self::assertSame($record['state'],$this->cli($root,['status',$id])['lifecycle']['state']);
        }
        self::assertSame(['ADMITTED','INSPECTING','COMPLETED'],$progress);
        $status=$this->cli($root,['status',$id]);$receipt=$status['receipt'];$snapshot=$receipt['snapshot'];
        self::assertCount(3,$status['lifecycle']['history']);self::assertCount(3,$status['lifecycle']['consumed_nonces']);
        self::assertSame($commit,$snapshot['commit_id']);self::assertSame($tree,$snapshot['tree_id']);
        self::assertTrue($snapshot['commit_verified']);self::assertTrue($snapshot['tree_verified']);
        self::assertSame($blob,$snapshot['findings'][0]['blob_id']);
        self::assertSame("Committed test-only bytes.\n",base64_decode($snapshot['findings'][0]['bytes_base64']));
        self::assertSame(hash('sha256',"Committed test-only bytes.\n"),$snapshot['findings'][0]['sha256']);
        self::assertSame(hash('sha256',$public),$receipt['trust_fingerprint']);
        self::assertFalse($receipt['deployment_isolation_claimed']);
        self::assertSame($before,$this->hashes($repo));
        file_put_contents($root.'/sanitized-test-mission-receipt.json',json_encode(['test_only'=>true,'root'=>$root,'progress'=>$progress,'target_unchanged'=>true,'receipt'=>$receipt],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        sodium_memzero($pair);
    }

    public function testBudgetMissingObjectTreeMismatchAndCompletionWithoutEvidenceRefuse():void
    {
        $f=new ProtectedMissionFixture();$payload=$f->state['authorizations'][$f->id]['payload'];
        $before=$this->hashes($payload['target']['repository']);
        foreach (['max_bytes'=>1,'max_files'=>0,'max_findings'=>0,'max_seconds'=>0] as $key=>$value) {
            $bad=$payload;$bad['budget'][$key]=$value;
            $expected=match($key){'max_bytes'=>'PMA_GIT_OBJECT_OR_BUDGET_INVALID','max_seconds'=>'PMA_INSPECTION_TIME_BUDGET',default=>'PMA_INSPECTION_BUDGET'};
            try {(new OfflineGitInspector())->inspect($bad);self::fail('budget bypass');}catch(\RuntimeException $e){self::assertSame($expected,$e->getMessage());}
            self::assertSame($before,$this->hashes($payload['target']['repository']));
        }
        $bad=$payload;$bad['target']['tree']=str_repeat('f',40);
        try {(new OfflineGitInspector())->inspect($bad);self::fail('tree substitution');}catch(\RuntimeException $e){self::assertSame('PMA_GIT_TREE_MISMATCH',$e->getMessage());}
        $bad=$payload;$bad['target']['commit']=str_repeat('f',40);
        try {(new OfflineGitInspector())->inspect($bad);self::fail('missing object');}catch(\RuntimeException $e){self::assertSame('PMA_LOOSE_OBJECT_ABSENT_NO_FETCH',$e->getMessage());}
        self::assertSame($before,$this->hashes($payload['target']['repository']));
        // Test-only corruption cannot manufacture an inspection receipt by moving the state.
        $f->state['lifecycles'][$f->id]['state']='INSPECTING';$f->save();
        $caps=$f->call('issue',['authorization_id'=>$f->id])['capabilities'];$before=hash_file('sha256',$f->root.'/authority.journal');
        try {$f->call('consume',['capability'=>$caps[2]]);self::fail('completion without proof');}catch(\RuntimeException $e){self::assertSame('PMA_INSPECTION_EVIDENCE_ABSENT',$e->getMessage());}
        self::assertSame($before,hash_file('sha256',$f->root.'/authority.journal'));
        self::assertNull($f->call('status',['authorization_id'=>$f->id])['receipt']);
    }

    private function cli(string $root,array $args,?array $input=null):array
    {
        [$code,$out,$err]=$this->process([PHP_BINARY,__DIR__.'/Support/protected_mission_cli.php',$root,...$args],$input===null?'':json_encode($input,JSON_THROW_ON_ERROR));
        self::assertSame(0,$code,$err);return json_decode($out,true,512,JSON_THROW_ON_ERROR);
    }
    public function testRealProcessCrashTailAndLostResponsePreserveSingleConsumption():void
    {
        $f=new ProtectedMissionFixture();$cap=$f->call('issue',['authorization_id'=>$f->id])['capabilities'][0];
        $worker=__DIR__.'/Support/protected_mission_crash_worker.php';
        [$code,$out]=$this->process([PHP_BINARY,$worker,$f->root,'torn-frame']);
        self::assertSame(23,$code);self::assertSame('',$out);
        self::assertSame('AUTHORIZED',$f->call('status',['authorization_id'=>$f->id])['lifecycle']['state']);
        $request=base64_encode(json_encode(['operation'=>'consume','arguments'=>['capability'=>$cap]],JSON_THROW_ON_ERROR));
        [$code,$out]=$this->process([PHP_BINARY,$worker,$f->root,'after-consume',$request]);
        self::assertSame(23,$code);self::assertSame('',$out);
        self::assertSame('ADMITTED',$f->call('status',['authorization_id'=>$f->id])['lifecycle']['state']);
        $before=hash_file('sha256',$f->root.'/authority.journal');
        try {$f->call('consume',['capability'=>$cap]);self::fail('replayed lost acknowledgement');}catch(\RuntimeException $e){self::assertSame('PMA_REPLAY',$e->getMessage());}
        self::assertSame($before,hash_file('sha256',$f->root.'/authority.journal'));
        self::assertCount(1,$f->call('status',['authorization_id'=>$f->id])['lifecycle']['history']);
    }
    public function testRealClockExpiryAfterIssuanceRefusesConsumptionWithoutResidue():void
    {
        $f=new ProtectedMissionFixture();$input=ProtectedMissionFixture::input();$expiry=time()+2;$input['mission']['expires_at']=$expiry;
        $cid=$f->call('prepare',$input)['challenge_id'];$p=$f->call('export',['challenge_id'=>$cid]);
        $f->call('submit',['challenge_id'=>$cid,'signature'=>$f->sign($p)]);$id=$f->call('derive',['challenge_id'=>$cid])['authorization_id'];
        $cap=$f->call('issue',['authorization_id'=>$id])['capabilities'][0];
        while(time()<$expiry) usleep(1000);
        $before=hash_file('sha256',$f->root.'/authority.journal');
        try {$f->call('consume',['capability'=>$cap]);self::fail('expired consumed');}catch(\RuntimeException $e){self::assertSame('PMA_AUTHORITY_INACTIVE',$e->getMessage());}
        self::assertSame($before,hash_file('sha256',$f->root.'/authority.journal'));
        self::assertSame('AUTHORIZED',$f->call('status',['authorization_id'=>$id])['lifecycle']['state']);
    }
    private function git(string $root,array $args):string
    {
        [$code,$out,$err]=$this->process(['git','-C',$root,'-c','core.hooksPath='.dirname($root).'/empty-hooks','-c','core.fsmonitor=false',...$args]);
        self::assertSame(0,$code,$err);return $out;
    }
    private function process(array $args,string $input=''):array
    {
        $p=proc_open($args,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,null,['bypass_shell'=>true]);
        fwrite($pipes[0],$input);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);fclose($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[2]);return [proc_close($p),$out,$err];
    }
    private function hashes(string $root):array
    {
        $out=[];foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root,\FilesystemIterator::SKIP_DOTS)) as $file) if($file->isFile())$out[substr($file->getPathname(),strlen($root))]=hash_file('sha256',$file->getPathname());ksort($out);return $out;
    }
}
