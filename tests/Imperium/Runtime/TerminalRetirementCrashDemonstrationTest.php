<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Imperium\Runtime\Evidence\TerminalRetirementCrashDemonstration;
use PHPUnit\Framework\TestCase;
final class TerminalRetirementCrashDemonstrationTest extends TestCase
{
 private string$directory;
 protected function setUp():void{$this->directory=sys_get_temp_dir().'/imperium-terminal-demo-'.bin2hex(random_bytes(5));}
 protected function tearDown():void{$this->remove($this->directory);}
 public function testFiveCrashBoundariesProducePrivateAndSanitizedEvidence():void{$result=(new TerminalRetirementCrashDemonstration(dirname(__DIR__,3)))->run($this->directory,new \DateTimeImmutable('2026-08-26T15:00:00+00:00'));self::assertSame('PROVED',$result['summary']['disposition']);self::assertFalse($result['summary']['continuing_operational_authority']);self::assertFileExists($result['private_evidence_file']);self::assertFileExists($result['sanitized_summary_file']);$private=$this->read($result['private_evidence_file']);self::assertSame(['PREPARED','CUSTODY_RESTORED','BINDING_RETIRED','TERMINAL_RECORDED','COMPLETE'],array_column($private['cases'],'crash_point'));self::assertTrue($private['single_winner_contention']['single_winner_proved']);foreach($private['cases']as$case){self::assertSame('PROVED',$case['disposition']);self::assertNotContains(false,$case['assertions']);}$sanitized=$this->read($result['sanitized_summary_file']);self::assertArrayNotHasKey('cases',$sanitized);self::assertStringNotContainsString('var/imperium',json_encode($sanitized,JSON_THROW_ON_ERROR));}
 private function read(string$p):array{return json_decode((string)file_get_contents($p),true,512,JSON_THROW_ON_ERROR);}
 private function remove(string$p):void{if(!is_dir($p))return;foreach(array_diff(scandir($p)?:[],['.','..'])as$e){$c=$p.'/'.$e;is_dir($c)?$this->remove($c):unlink($c);}rmdir($p);}
}
