<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
final class DelegateMissionDeploymentCorridorPersistenceTest extends TestCase
{
 #[DataProvider('services')]
 public function testBoundaryUsesCanonicalValidationAndImmutablePersistence(string$path,string$conflict):void{$source=file_get_contents(dirname(__DIR__,3).'/'.$path);self::assertIsString($source);self::assertStringContainsString('RecordReferenceValidator',$source);self::assertStringContainsString('ImmutableRecordStore',$source);self::assertStringContainsString('->read(',$source);self::assertStringContainsString('->isIntact(',$source);self::assertStringContainsString('->put(',$source);self::assertStringContainsString($conflict,$source);self::assertStringNotContainsString('file_get_contents',$source);self::assertStringNotContainsString('file_put_contents',$source);}
 public static function services():iterable{yield'deployment authorization'=>['src/Imperium/Runtime/Curia/DelegateMissionDeploymentAuthorizationService.php','C249_DELEGATE_MISSION_DEPLOYMENT_CONFLICT'];yield'runtime activation'=>['src/Imperium/Runtime/Conscription/DelegateMissionRuntimeActivationService.php','R279_DELEGATE_MISSION_ACTIVATION_CONFLICT'];}
}
