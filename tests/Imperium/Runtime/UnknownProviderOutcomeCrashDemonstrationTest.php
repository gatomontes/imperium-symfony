<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Evidence\UnknownProviderOutcomeCrashDemonstration;
use PHPUnit\Framework\TestCase;

final class UnknownProviderOutcomeCrashDemonstrationTest extends TestCase
{
    private string $directory;
    protected function setUp():void{$this->directory=sys_get_temp_dir().'/imperium-provider-demo-'.bin2hex(random_bytes(5));}
    protected function tearDown():void{$this->remove($this->directory);}
    public function testUnknownOutcomeAndSealedResponseRecoveryAreBothProved():void
    {
        $result=(new UnknownProviderOutcomeCrashDemonstration(dirname(__DIR__,3)))->run($this->directory,new \DateTimeImmutable('2026-08-26T14:00:00+00:00'));
        self::assertSame('PROVED',$result['summary']['disposition']);self::assertFalse($result['summary']['provider_reinvoked']);
        self::assertFileExists($result['private_evidence_file']);self::assertFileExists($result['sanitized_summary_file']);
        $private=$this->read($result['private_evidence_file']);
        self::assertSame('PROVED',$private['unknown_outcome_case']['disposition']);self::assertNotContains(false,$private['unknown_outcome_case']['assertions']);
        self::assertSame('PROVED',$private['sealed_response_recovery_case']['disposition']);self::assertNotContains(false,$private['sealed_response_recovery_case']['assertions']);
        $sanitized=$this->read($result['sanitized_summary_file']);self::assertArrayNotHasKey('unknown_outcome_case',$sanitized);$encoded=json_encode($sanitized,JSON_THROW_ON_ERROR);self::assertStringNotContainsString('var/imperium',$encoded);self::assertStringNotContainsString('synthetic-demonstration-provider',$encoded);
    }
    private function read(string$p):array{return json_decode((string)file_get_contents($p),true,512,JSON_THROW_ON_ERROR);}
    private function remove(string$p):void{if(!is_dir($p))return;foreach(array_diff(scandir($p)?:[],['.','..'])as$e){$c=$p.'/'.$e;is_dir($c)?$this->remove($c):unlink($c);}rmdir($p);}
}
