<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ExistingInstanceImperatorPrincipalRemediationService;
use App\Imperium\Runtime\Imperator\ImperatorPrincipalConstitutionAuthorityContract;
use App\Imperium\Runtime\Imperator\ImperatorPrincipalProvenanceFixtureStore;
use PHPUnit\Framework\TestCase;
final class ProviderBindingActivationPrincipalProvenanceBatch5Test extends TestCase
{
    private string $root;
    protected function setUp():void{$this->root=sys_get_temp_dir().'/imperium-ppr-b5-'.bin2hex(random_bytes(6));mkdir($this->root,0770,true);}
    protected function tearDown():void{if(!is_dir($this->root))return;$i=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root,\FilesystemIterator::SKIP_DOTS),\RecursiveIteratorIterator::CHILD_FIRST);foreach($i as$x)$x->isDir()?rmdir($x->getPathname()):unlink($x->getPathname());rmdir($this->root);}
    public function testExactRemediationAuthorityConstitutesOnlyMissingPendingPrincipal():void
    {
        $seal=$this->seal(['schema'=>'imperium.operator-root-operationalization-seal/v1','seal_id'=>'operationalization-test','instance_id'=>'imperium-test','status'=>'IMPERIUM_OPERATIONAL']);$d=$this->root.'/var/imperium/operator-root';mkdir($d,0770,true);file_put_contents($d.'/operationalization-seal.json',json_encode($seal,JSON_THROW_ON_ERROR));
        $a=$this->authority($seal);(new ImperatorPrincipalProvenanceFixtureStore($this->root))->putConstitutionAuthority($a);$s=new ExistingInstanceImperatorPrincipalRemediationService($this->root);$at=new \DateTimeImmutable('2026-08-30T00:01:00+00:00');$p=$s->remediate($a['authority_id'],$at);self::assertSame($p,$s->remediate($a['authority_id'],$at));self::assertSame('PENDING_ACTIVATION',$p['status']);self::assertSame('EXISTING_INSTANCE_REMEDIATION',$p['constitution_route']);self::assertSame(1,$p['principal_generation']);self::assertFalse($p['credential_secret_persisted']);
    }
    public function testDocumentationAuthorizesCallerIssuerHardeningOnlyNext():void{$h=(string)file_get_contents(dirname(__DIR__,3).'/docs/handoffs/provider-binding-activation-principal-provenance-remediation-batch-5-complete.md');foreach(['Only Batch 6 is authorized','caller-authority issuer hardening','canonical active principal','may not activate a principal','reconsider corridor disposition','external I/O','Iron Gate','Lazaretto']as$b)self::assertNotFalse(stripos($h,$b),$b);}
    private function authority(array$seal):array{$r=['schema'=>ImperatorPrincipalConstitutionAuthorityContract::SCHEMA,'authority_id'=>'imperator-principal-remediation-authority-test','instance_id'=>'imperium-test','route'=>'EXISTING_INSTANCE_REMEDIATION','operator_root'=>['operator_id'=>'operator-test','source_identity_digest'=>str_repeat('1',64),'decision_id'=>'decision-test','decision_digest'=>str_repeat('2',64)],'operationalization'=>['id'=>$seal['seal_id'],'digest'=>$seal['record_digest'],'schema'=>$seal['schema']],'imperator_identity'=>['operator_id'=>'operator-test','operator_identity_digest'=>str_repeat('1',64),'imperator_subject_id'=>'imperator-subject-test','imperator_subject_digest'=>str_repeat('4',64)],'permitted_transition'=>'REMEDIATE_MISSING_IMPERATOR_PRINCIPAL','target_principal'=>['principal_id'=>'imperator-principal-test','binding_id'=>'imperator-binding-test','generation'=>1],'scope'=>['provider_binding_activation_authority'=>true,'outbound_email_authority'=>false,'credential_authority'=>false,'provider_execution_authority'=>false,'corridor_disposition_authority'=>false],'authority_single_use'=>true,'authority_exercisable'=>true,'issued_at'=>'2026-08-30T00:00:00+00:00','expires_at'=>'2026-08-30T00:10:00+00:00','consumed'=>false,'continuing_authority'=>false,'sealed'=>true];return $this->seal($r);}
    private function seal(array$r):array{$r['record_digest']=hash('sha256',CanonicalJson::encode($r));return$r;}
}
