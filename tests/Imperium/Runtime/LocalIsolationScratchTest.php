<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\ProtectedMission\ScratchWorkspace;
use App\Tests\Imperium\Runtime\Support\ProtectedMissionFixture;
use PHPUnit\Framework\TestCase;

final class LocalIsolationScratchTest extends TestCase
{
    public function testRealCeremonyCleansAndRefusalPreservesJournal(): void
    {
        $f=new ProtectedMissionFixture();
        $scratch=ScratchWorkspace::root($f->root);
        self::assertSame([],iterator_to_array(new \FilesystemIterator($scratch)));
        $before=hash_file('sha256',$f->root.'/authority.journal');
        $bad=ProtectedMissionFixture::input();$bad['disclosures']=[];
        $this->refuses('C246_PLANNING_DOSSIER_INPUT_INVALID',fn()=>$f->call('prepare',$bad));
        self::assertSame([],iterator_to_array(new \FilesystemIterator($scratch)));
        self::assertSame($before,hash_file('sha256',$f->root.'/authority.journal'));
        mkdir($scratch.'/unknown');file_put_contents($scratch.'/unknown/evidence','preserve');
        $this->refuses('PMA_SCRATCH_ABANDONED_OR_BUSY',fn()=>$f->call('prepare',ProtectedMissionFixture::input()));
        $this->refuses('PMA_SCRATCH_ABANDONED_OR_BUSY',fn()=>$f->call('issue',['authorization_id'=>$f->id]));
        self::assertSame('AUTHORIZED',$f->call('status',['authorization_id'=>$f->id])['lifecycle']['state']);
        self::assertSame($before,hash_file('sha256',$f->root.'/authority.journal'));
        self::assertSame('preserve',file_get_contents($scratch.'/unknown/evidence'));
    }

    public function testMissingAndSubstitutedRootRefuseWithoutCreatingIt(): void
    {
        $root=str_replace('\\','/',sys_get_temp_dir()).'/imperium-scratch-missing-'.bin2hex(random_bytes(8));
        $this->refuses('PMA_SCRATCH_ROOT_REQUIRED',fn()=>ScratchWorkspace::run($root,fn()=>[]));
        self::assertDirectoryDoesNotExist(ScratchWorkspace::root($root));
        mkdir(ScratchWorkspace::root($root));
        $this->refuses('PMA_SCRATCH_PATH_SUBSTITUTED',fn()=>ScratchWorkspace::run(dirname($root).'/./'.basename($root),fn()=>[]));
    }

    public function testCleanupTouchesOnlyItsExactWorkspaceAndPreservesOtherWork(): void
    {
        $root=str_replace('\\','/',sys_get_temp_dir()).'/imperium-scratch-cleanup-'.bin2hex(random_bytes(8));
        $scratch=ScratchWorkspace::root($root);mkdir($scratch);
        $this->refuses('DISPOSABLE_EXCEPTION',fn()=>ScratchWorkspace::run($root,function($path)use($scratch):array{
            mkdir($path.'/nested');file_put_contents($path.'/nested/file','test');
            file_put_contents($scratch.'/unrelated','preserve');
            throw new \RuntimeException('DISPOSABLE_EXCEPTION');
        }));
        self::assertSame(['unrelated'],array_values(array_diff(scandir($scratch),['.','..'])));
        self::assertSame('preserve',file_get_contents($scratch.'/unrelated'));
    }

    public function testNativeWorkspacePolicyAndCleanupFailure(): void
    {
        if(PHP_OS_FAMILY!=='Windows') self::markTestSkipped('Windows ACL and handle semantics required.');
        $p=proc_open(['pwsh','-NoProfile','-File',__DIR__.'/Support/local_isolation_scratch_native.ps1'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
        self::assertIsResource($p);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);fclose($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[2]);
        self::assertSame(0,proc_close($p),$out.$err);
        $proof=json_decode($out,true,512,JSON_THROW_ON_ERROR);
        self::assertSame('SCRATCH_NATIVE_COMPONENTS_PASSED',$proof['result']);
        self::assertFalse($proof['equivalent_ceremony_proof']);
    }

    private function refuses(string $message,callable $call):void
    {
        try{$call();self::fail('Expected '.$message);}catch(\RuntimeException $e){self::assertSame($message,$e->getMessage());}
    }
}
