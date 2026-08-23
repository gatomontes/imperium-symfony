<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\IronGate;
use App\Imperium\Runtime\LaCortine\Lazaretto;
use App\Imperium\Runtime\LaCortine\RawExternalPayload;
use App\Imperium\Runtime\Oracle\CanonicalCatalogueSnapshotService;
use App\Imperium\Runtime\Oracle\ModelIntelligenceLedgerService;
use App\Imperium\Runtime\Oracle\OracleResearchCommissionService;
use App\Imperium\Runtime\Oracle\OracleResearchEvidenceAdmissionService;
use PHPUnit\Framework\TestCase;

final class OracleGovernedResearchFlowTest extends TestCase
{
    public function testImperatorCommissionReturnsThroughLazarettoAndConsumesResearchAuthority():void
    {
        $root=sys_get_temp_dir().'/imperium-oracle-research-'.bin2hex(random_bytes(6));$now=new \DateTimeImmutable('2026-08-23T12:00:00+00:00');
        try{
            $commissions=new OracleResearchCommissionService($root);$commission=$commissions->issue('imperium-test',$this->scope(),$now,$now->modify('+10 minutes'),$this->authorization(),$this->augur());
            self::assertTrue($commission['external_research_authority_exercisable']);self::assertFalse($commission['provider_invocation_authority']);self::assertFalse($commission['selection_authority']);
            $dispatch=(new IronGate())->dispatch($commissions->outboundRequest($commission),$now->modify('+1 second'));self::assertNotNull($dispatch->sortie);
            $content=json_encode($this->returnBody(),JSON_THROW_ON_ERROR);$payload=new RawExternalPayload('payload-oracle-research',$dispatch->executionId,$commission['commission_id'],$commission['authorization']['id'],$dispatch->sortie->sortieId,$dispatch->sortie->manifestationId,$content,hash('sha256',$content),['https://provider.test/models/gpt-test'],['web.read'],['capability-web-research'],$now->modify('+2 seconds'),$now->modify('+3 seconds'));
            $artifact=(new Lazaretto())->admit($payload,$dispatch,$now->modify('+4 seconds'));
            $admission=new OracleResearchEvidenceAdmissionService($root);$receipt=$admission->admit($commission['commission_id'],$artifact,$this->augur());
            self::assertSame($receipt,$admission->admit($commission['commission_id'],$artifact,$this->augur()));self::assertSame('ORACLE_RESEARCH_EVIDENCE_ADMITTED_AUTHORITY_CONSUMED',$receipt['status']);self::assertTrue($receipt['external_research_authority_consumed']);self::assertFalse($receipt['external_research_authority_exercisable']);
            foreach(['credential_use_authority','provider_invocation_authority','eligibility_authority','recommendation_authority','selection_authority','model_assignment_authority','deployment_authority']as$a)self::assertFalse($receipt[$a]);
            $snapshot=(new CanonicalCatalogueSnapshotService($root,new ModelIntelligenceLedgerService($root)))->seal('imperium-test',[['evidence_id'=>$receipt['evidence']['id'],'clavium_assertion_id'=>null]],$this->augur());
            self::assertSame('ORACLE_CANONICAL_CATALOGUE_SNAPSHOT_SEALED_NO_SELECTION_AUTHORITY',$snapshot['status']);self::assertSame('UNVERIFIED',$snapshot['models']['openai/gpt-test@2026-08-01']['accessibility']['status']);
            self::assertSame($commission['commission_id'],$snapshot['models']['openai/gpt-test@2026-08-01']['provenance']['commission_id']);
        }finally{$this->remove($root);}
    }

    public function testAugurCannotMintItsOwnStandingResearchAuthority():void
    {
        $root=sys_get_temp_dir().'/imperium-oracle-research-invalid-'.bin2hex(random_bytes(6));$auth=$this->authorization();$auth['issuer']='oracle';$auth=$this->digest($auth);$now=new \DateTimeImmutable('2026-08-23T12:00:00+00:00');
        try{$this->expectException(\RuntimeException::class);$this->expectExceptionMessage('OR27_RESEARCH_AUTHORIZATION_INVALID');(new OracleResearchCommissionService($root))->issue('imperium-test',$this->scope(),$now,$now->modify('+10 minutes'),$auth,$this->augur());}finally{$this->remove($root);}
    }

    private function scope():array{return['providers'=>['openai'],'destinations'=>['https://provider.test/models/gpt-test'],'claim_subjects'=>['capability'],'tool_ids'=>['web.read'],'capability_ids'=>['capability-web-research'],'max_sources'=>1,'budget_units'=>10];}
    private function returnBody():array{$source=['source_id'=>'provider-model-page','source_type'=>'provider-documentation','locator'=>'https://provider.test/models/gpt-test','observed_at'=>'2026-08-23T12:00:02+00:00','content_digest'=>'sha256:'.hash('sha256','provider-model-page')];return['provider'=>'openai','model_id'=>'gpt-test','model_version'=>'2026-08-01','knowledge_sources'=>[$source],'claims'=>[['claim_id'=>'structured-output','subject'=>'capability','value'=>'structured-output','evidence_source_ids'=>['provider-model-page']]],'admissibility'=>['status'=>'UNEVALUATED','policy_refs'=>[],'evidence_source_ids'=>[],'reasons'=>[]]];}
    private function authorization():array{return$this->digest(['schema'=>'imperium.oracle-research-authorization/v1','authorization_id'=>'imperator-oracle-research-authorization','instance_id'=>'imperium-test','issuer'=>'imperator','oracle_research_commission_authority'=>true,'model_selection_authority'=>false,'sealed'=>true]);}
    private function augur():array{return['instance_id'=>'imperium-test','office'=>'oracle','seat'=>'oracle.augur','binding_id'=>'oracle-augur-binding','manifestation_id'=>'oracle-augur-manifestation','occupancy_generation'=>1,'status'=>'ACTIVE','model_intelligence_stewardship_authority'=>true,'model_research_authority'=>false,'model_selection_authority'=>false];}
    private function digest(array$r):array{unset($r['record_digest']);$r['record_digest']='sha256:'.hash('sha256',CanonicalJson::encode($r));return$r;}
    private function remove(string$p):void{if(!is_dir($p))return;foreach(array_diff(scandir($p)?:[],['.','..'])as$e){$c=$p.'/'.$e;is_dir($c)?$this->remove($c):unlink($c);}rmdir($p);}
}
