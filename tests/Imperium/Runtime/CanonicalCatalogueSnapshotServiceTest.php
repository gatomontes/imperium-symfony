<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Oracle\CanonicalCatalogueSnapshotService;
use App\Imperium\Runtime\Oracle\ModelIntelligenceLedgerService;
use PHPUnit\Framework\TestCase;

final class CanonicalCatalogueSnapshotServiceTest extends TestCase
{
    public function testAugurSealsFirstCatalogueFromPersistedEvidenceAndClaviumAssertionWithoutResearchOrSelection():void
    {
        $root=sys_get_temp_dir().'/imperium-oracle-canonical-'.bin2hex(random_bytes(6));
        try{
            $evidence=$this->evidence();$assertion=$this->assertion();
            $this->write($root.'/var/imperium/offices/oracle/admitted-model-evidence/'.$evidence['evidence_id'].'.json',$evidence);
            $this->write($root.'/var/imperium/offices/clavium/provider-access-assertions/'.$assertion['assertion_id'].'.json',$assertion);
            $service=new CanonicalCatalogueSnapshotService($root,new ModelIntelligenceLedgerService($root));
            $snapshot=$service->seal('imperium-test',[['evidence_id'=>$evidence['evidence_id'],'clavium_assertion_id'=>$assertion['assertion_id']]],$this->augur());
            self::assertSame('ORACLE_CANONICAL_CATALOGUE_SNAPSHOT_SEALED_NO_SELECTION_AUTHORITY',$snapshot['status']);
            self::assertSame('KNOWN',$snapshot['models']['openai/gpt-test@2026-08-01']['knowledge']['status']);
            self::assertSame('ACCESSIBLE',$snapshot['models']['openai/gpt-test@2026-08-01']['accessibility']['status']);
            self::assertSame('ADMISSIBLE',$snapshot['models']['openai/gpt-test@2026-08-01']['admissibility']['status']);
            foreach(['model_research_authority','eligibility_authority','recommendation_authority','selection_authority','model_assignment_authority','credential_disclosure_authority','provider_invocation_authority','deployment_authority']as$a)self::assertFalse($snapshot[$a]);
            self::assertStringNotContainsString('secret',strtolower(json_encode($snapshot,JSON_THROW_ON_ERROR)));
        }finally{$this->removeTree($root);}
    }

    public function testCatalogueRejectsEvidenceAndAssertionFromDifferentProviders():void
    {
        $root=sys_get_temp_dir().'/imperium-oracle-canonical-invalid-'.bin2hex(random_bytes(6));
        try{$e=$this->evidence();$a=$this->assertion();$a['provider']='anthropic';$a=$this->digest($a);
            $this->write($root.'/var/imperium/offices/oracle/admitted-model-evidence/'.$e['evidence_id'].'.json',$e);$this->write($root.'/var/imperium/offices/clavium/provider-access-assertions/'.$a['assertion_id'].'.json',$a);
            $this->expectException(\RuntimeException::class);$this->expectExceptionMessage('OR23_CATALOGUE_LINEAGE_MISMATCH');
            (new CanonicalCatalogueSnapshotService($root,new ModelIntelligenceLedgerService($root)))->seal('imperium-test',[['evidence_id'=>$e['evidence_id'],'clavium_assertion_id'=>$a['assertion_id']]],$this->augur());
        }finally{$this->removeTree($root);}
    }

    private function evidence():array{$source=['source_id'=>'openai-doc','source_type'=>'admitted-provider-documentation','locator'=>'evidence://openai/gpt-test','observed_at'=>'2026-08-23T12:00:00+00:00','content_digest'=>'sha256:'.hash('sha256','evidence')];return$this->digest(['schema'=>'imperium.oracle-admitted-model-evidence/v1','evidence_id'=>'evidence-openai-gpt-test','instance_id'=>'imperium-test','provider'=>'openai','model_id'=>'gpt-test','model_version'=>'2026-08-01','knowledge_sources'=>[$source],'claims'=>[['claim_id'=>'structured-output','subject'=>'capability','value'=>'structured-output','evidence_source_ids'=>['openai-doc']]],'admissibility'=>['status'=>'ADMISSIBLE','policy_refs'=>['imperium.model-policy/v1'],'evidence_source_ids'=>['openai-doc'],'reasons'=>[]],'research_lineage'=>null,'status'=>'EVIDENCE_ADMITTED','admitted_by'=>['office'=>'oracle','process'=>'preexisting-evidence-intake'],'model_research_authority'=>false,'sealed'=>true]);}
    private function assertion():array{return$this->digest(['schema'=>'imperium.clavium-provider-access-assertion/v1','assertion_id'=>'clavium-provider-access-openai','instance_id'=>'imperium-test','issuer'=>['office'=>'clavium','officer'=>'locksmith','seat'=>'clavium.locksmith','binding_id'=>'binding','manifestation_id'=>'manifestation','occupancy_generation'=>1],'provider'=>'openai','credential_ref'=>'clavium://providers/openai/default','scope'=>['model.invoke'],'observation'=>['method'=>'mechanical-presence','observed_at'=>'2026-08-23T12:00:00+00:00','evidence'=>['non_empty_credential_present'=>true]],'status'=>'ACCESS_AVAILABLE','checkpoint'=>'CLAVIUM_PROVIDER_ACCESS_ASSERTION_SEALED_NO_USE_AUTHORITY','restrictions'=>[],'revalidation'=>['expires_at'=>'2026-08-24T12:00:00+00:00','conditions'=>['expiry']],'credential_possession_transferred'=>false,'credential_use_authority'=>false,'credential_disclosure_authority'=>false,'provider_invocation_authority'=>false,'model_admissibility_authority'=>false,'model_selection_authority'=>false,'execution_authority'=>false,'sealed'=>true]);}
    private function augur():array{return['instance_id'=>'imperium-test','office'=>'oracle','seat'=>'oracle.augur','binding_id'=>'binding-augur','manifestation_id'=>'manifestation-augur','occupancy_generation'=>1,'status'=>'ACTIVE','model_intelligence_stewardship_authority'=>true,'model_selection_authority'=>false];}
    private function digest(array$r):array{unset($r['record_digest']);$r['record_digest']='sha256:'.hash('sha256',CanonicalJson::encode($r));return$r;}
    private function write(string$p,array$r):void{if(!is_dir(dirname($p)))mkdir(dirname($p),0770,true);file_put_contents($p,json_encode($r,JSON_THROW_ON_ERROR));}
    private function removeTree(string$p):void{if(!is_dir($p))return;foreach(array_diff(scandir($p)?:[],['.','..'])as$e){$c=$p.'/'.$e;is_dir($c)?$this->removeTree($c):unlink($c);}rmdir($p);}
}
