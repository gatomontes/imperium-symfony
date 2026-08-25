<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use PHPUnit\Framework\TestCase;
final class DelegateMissionDeploymentCustodyMigrationTest extends TestCase
{
 public function testCustodyTransitionUsesCanonicalValidationAndRecoverableCoordinator():void{$source=file_get_contents(dirname(__DIR__,3).'/src/Imperium/Runtime/Garrison/DelegateMissionOperationalCustodyTransitionService.php');self::assertIsString($source);self::assertStringContainsString('RecordReferenceValidator',$source);self::assertStringContainsString('DelegateMissionDeploymentCustodyTransitionCoordinator',$source);self::assertStringContainsString('resumeForAuthorization',$source);self::assertStringNotContainsString('file_get_contents',$source);self::assertStringNotContainsString('file_put_contents',$source);}
}
