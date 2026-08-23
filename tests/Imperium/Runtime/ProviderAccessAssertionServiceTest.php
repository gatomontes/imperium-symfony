<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clavium\EnvironmentCredentialPresenceProbe;
use App\Imperium\Runtime\Clavium\ProviderAccessAssertionService;
use App\Imperium\Runtime\Clavium\ProviderAccessProbe;
use PHPUnit\Framework\TestCase;

final class ProviderAccessAssertionServiceTest extends TestCase
{
    public function testOccupiedLocksmithSealsMechanicalAvailabilityWithoutUseAuthorityOrSecretMaterial(): void
    {
        $root=sys_get_temp_dir().'/imperium-clavium-assertion-'.bin2hex(random_bytes(6));
        $probe=new class implements ProviderAccessProbe { public function observe(string$p,string$r,array$s):array{return['status'=>'ACCESS_AVAILABLE','method'=>'sterile-test-presence','evidence'=>['credential_reference_configured'=>true,'non_empty_secret_present'=>true],'restrictions'=>[]];} };
        try {
            $service=new ProviderAccessAssertionService($root,$probe);$binding=$this->binding();
            $observed=new \DateTimeImmutable('2026-08-23T12:00:00+00:00');$expires=$observed->modify('+1 day');
            $assertion=$service->assert('openai','clavium://providers/openai/default',['model.invoke'],$observed,$expires,$binding);
            self::assertSame($assertion,$service->assert('openai','clavium://providers/openai/default',['model.invoke'],$observed,$expires,$binding));
            self::assertSame('imperium.clavium-provider-access-assertion/v1',$assertion['schema']);self::assertSame('ACCESS_AVAILABLE',$assertion['status']);
            self::assertSame('CLAVIUM_PROVIDER_ACCESS_ASSERTION_SEALED_NO_USE_AUTHORITY',$assertion['checkpoint']);
            self::assertSame('clavium',$assertion['issuer']['office']);self::assertSame('locksmith',$assertion['issuer']['officer']);self::assertSame('clavium.locksmith',$assertion['issuer']['seat']);
            self::assertStringStartsWith('clavium://',$assertion['credential_ref']);self::assertStringStartsWith('sha256:',$assertion['record_digest']);
            foreach(['credential_possession_transferred','credential_use_authority','credential_disclosure_authority','provider_invocation_authority','model_admissibility_authority','model_selection_authority','execution_authority']as$f)self::assertFalse($assertion[$f]);
            self::assertArrayNotHasKey('secret',$assertion);self::assertArrayNotHasKey('token',$assertion);self::assertArrayNotHasKey('api_key',$assertion);
            self::assertFileExists($root.'/var/imperium/offices/clavium/provider-access-assertions/'.$assertion['assertion_id'].'.json');
        } finally {$this->removeTree($root);}
    }

    public function testEnvironmentProbeReportsPresenceWithoutReturningCredential(): void
    {
        $_ENV['IMPERIUM_TEST_PROVIDER_KEY']='top-secret-never-return';
        try {
            $observation=(new EnvironmentCredentialPresenceProbe(['clavium://providers/test/default'=>'IMPERIUM_TEST_PROVIDER_KEY']))->observe('test','clavium://providers/test/default',['model.invoke']);
            self::assertSame('ACCESS_AVAILABLE',$observation['status']);self::assertTrue($observation['evidence']['non_empty_secret_present']);
            self::assertStringNotContainsString('top-secret-never-return',json_encode($observation,JSON_THROW_ON_ERROR));
        } finally {unset($_ENV['IMPERIUM_TEST_PROVIDER_KEY']);}
    }

    private function binding():array
    {
        $r=['schema'=>'imperium.clavium-locksmith-occupancy/v1','binding_id'=>'clavium-locksmith-binding-1234567890abcdef1234','instance_id'=>'imperium-test','office'=>'clavium','seat'=>'clavium.locksmith','manifestation_id'=>'manifestation-locksmith','occupancy_generation'=>1,'status'=>'ACTIVE','provider_access_assertion_authority'=>true,'credential_disclosure_authority'=>false,'provider_invocation_authority'=>false,'execution_authority'=>false];
        $r['record_digest']=hash('sha256',CanonicalJson::encode($r));return$r;
    }
    private function removeTree(string$p):void{if(!is_dir($p))return;foreach(array_diff(scandir($p)?:[],['.','..'])as$e){$c=$p.'/'.$e;is_dir($c)?$this->removeTree($c):unlink($c);}rmdir($p);}
}
