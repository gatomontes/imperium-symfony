<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\ProtectedMission\ProofProcess;
use PHPUnit\Framework\TestCase;

final class LocalIsolationProcessTest extends TestCase
{
    private function child(string $mode, string $input): array
    {
        $marker=sys_get_temp_dir().'/pma-process-'.bin2hex(random_bytes(8));
        $result=ProofProcess::run([PHP_BINARY,__DIR__.'/Support/proof_process_child.php',$mode,$marker],$input);
        self::assertSame('called\n',file_get_contents($marker),'Child must be invoked exactly once');
        unlink($marker);
        return $result;
    }
    public function testCompleteInputArrivesUnchanged(): void
    {
        $input=str_repeat("disposable\0input",8192);
        $r=$this->child('read',$input);
        self::assertSame(hash('sha256',$input),$r['output']);
        self::assertSame('success',ProofProcess::check($r,'prepare')['outcome']);
    }
    public function testEarlyRefusalIsExplicitAndDoesNotRetry(): void
    {
        $r=$this->child('refuse',str_repeat('inert',1024*1024));
        self::assertSame('incomplete',$r['input_delivery']);
        self::assertSame(2,$r['exit_code']);
        self::assertSame('expected_refusal',ProofProcess::check($r,'abandoned-prepare','PMA_INSTALLATION_CHECK_FAILED')['outcome']);
        $this->expectExceptionMessage('PROOF_PROCESS_REFUSED');
        ProofProcess::check($r,'prepare');
    }
    public function testSuccessfulExitDoesNotExcuseIncompleteDelivery(): void
    {
        $r=$this->child('close',str_repeat('inert',1024*1024));
        self::assertSame(0,$r['exit_code']);self::assertSame('incomplete',$r['input_delivery']);
        $this->expectExceptionMessage('PROOF_PROCESS_REFUSED');ProofProcess::check($r,'submit');
    }
    public function testUnexpectedChildErrorIsNotDisclosedOrAcceptedAsRefusal(): void
    {
        $r=$this->child('private-error',str_repeat('inert',1024*1024));
        try { ProofProcess::check($r,'abandoned-prepare','PMA_INSTALLATION_CHECK_FAILED');self::fail(); }
        catch(\RuntimeException $e){self::assertStringNotContainsString('PRIVATE_SENTINEL',$e->getMessage());self::assertStringContainsString('"exit_code":7',$e->getMessage());}
    }
    public function testShortWritesAndZeroProgress(): void
    {
        stream_wrapper_register('pmashort',ProofShortStream::class);
        try {
            ProofShortStream::$bytes='';ProofShortStream::$limit=100;
            $s=fopen('pmashort://fixture','w');self::assertTrue(ProofProcess::writeInput($s,'0123456789'));fclose($s);
            self::assertSame('0123456789',ProofShortStream::$bytes);
            ProofShortStream::$bytes='';ProofShortStream::$limit=4;
            $s=fopen('pmashort://fixture','w');self::assertFalse(ProofProcess::writeInput($s,'0123456789'));fclose($s);
            self::assertSame('0123',ProofShortStream::$bytes);
        } finally {stream_wrapper_unregister('pmashort');}
    }
    public function testWindowsModulePollutionAtBothRealLaunchBoundaries(): void
    {
        if(PHP_OS_FAMILY!=='Windows')self::markTestSkipped('Native Windows PowerShell required');
        $r=ProofProcess::run(['pwsh','-NoProfile','-File',__DIR__.'/Support/local_isolation_startup_modules.ps1']);
        self::assertSame(0,$r['exit_code'],$r['error']);
        self::assertSame('POLLUTED_BASELINE_REPRODUCED_BOTH_CHILD_BOUNDARIES_PASSED_PARENT_UNCHANGED',trim($r['output']));
    }
}
final class ProofShortStream
{
    public $context;
    public static string $bytes='';
    public static int $limit=100;
    public function stream_open($path,$mode,$options,&$opened): bool{return true;}
    public function stream_write($data): int
    {
        $n=min(3,strlen($data),self::$limit-strlen(self::$bytes));
        self::$bytes.=substr($data,0,$n);return $n;
    }
    public function stream_close(): void {}
}
