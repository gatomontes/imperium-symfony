<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use PHPUnit\Framework\TestCase;

final class LocalIsolationReadinessTest extends TestCase
{
    public function testNativeHandlesAndExactStartupRefusal(): void
    {
        if (PHP_OS_FAMILY !== 'Windows') self::markTestSkipped('Native Windows handles and Windows PowerShell 5.1 required.');
        $process=proc_open(['pwsh','-NoProfile','-File',__DIR__.'/Support/local_isolation_native_readiness.ps1'],
            [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,null,['bypass_shell'=>true]);
        self::assertIsResource($process);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);fclose($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[2]);
        self::assertSame(0,proc_close($process),$err."\n".$out);
        $proof=json_decode($out,true,512,JSON_THROW_ON_ERROR);
        self::assertSame('LOCAL_ISOLATION_NATIVE_READINESS_PASSED',$proof['result']);
        self::assertTrue($proof['bytes_unchanged']);self::assertFalse($proof['actual_account_isolation']);
    }
    public function testCompleteReadinessAndAdversarialEvidenceThroughPowerShell(): void
    {
        if (PHP_OS_FAMILY !== 'Windows') self::markTestSkipped('Windows PowerShell/native readiness route required.');
        $process=proc_open(['pwsh','-NoProfile','-File',__DIR__.'/Support/local_isolation_readiness.ps1'],
            [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,null,['bypass_shell'=>true]);
        self::assertIsResource($process);
        fclose($pipes[0]);$out=stream_get_contents($pipes[1]);fclose($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[2]);
        self::assertSame(0,proc_close($process),$err."\n".$out);
        $proof=json_decode($out,true,512,JSON_THROW_ON_ERROR);
        self::assertSame('LOCAL_ISOLATION_READINESS_FIXTURES_PASSED',$proof['result']);
        self::assertSame(42,$proof['negative_cases']);
        self::assertSame($proof['cases'],array_values(array_unique($proof['cases'])));
        self::assertFalse($proof['actual_account_isolation']);
    }
}
