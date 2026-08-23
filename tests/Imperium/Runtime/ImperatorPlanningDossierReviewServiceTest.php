<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\ImperatorPlanningDossierReviewService;
use PHPUnit\Framework\TestCase;

final class ImperatorPlanningDossierReviewServiceTest extends TestCase
{
    private string$root;private array$dossier;
    protected function setUp():void{$this->root=sys_get_temp_dir().'/imperium-dossier-review-'.bin2hex(random_bytes(6));$lines=[];foreach(['Mission objective: construct an attributable Persona.','Proposed model for Foundry Artificer: openai/gpt-test@2026-08-01.','Estimated model cost ceiling: 25 units.']as$i=>$text){$line=['line_number'=>$i+1,'section'=>['mission_plan.objective','proposed_model_binding','disclosure.cost_time_retention_limits'][$i],'text'=>$text,'source'=>'fixture'];$line['line_digest']=hash('sha256',CanonicalJson::encode($line));$lines[]=$line;}$this->dossier=$this->digest(['schema'=>'imperium.curia-planning-dossier/v1','dossier_id'=>'curia-planning-dossier-'.str_repeat('a',20),'dossier_version'=>1,'instance_id'=>'imperium-test','lines'=>$lines,'line_count'=>count($lines),'imperator_review_authority'=>['authority_id'=>'imperator-planning-dossier-review-authority-'.str_repeat('b',20),'authority_single_use'=>true,'permitted_dispositions'=>['APPROVE_DOSSIER','OBJECT_RETURN_FOR_REVISION'],'review_authority'=>true],'status'=>'CURIA_PLANNING_DOSSIER_SEALED_PENDING_IMPERATOR_REVIEW']);$this->write($this->root.'/var/imperium/offices/curia/planning-dossiers/'.$this->dossier['dossier_id'].'.json',$this->dossier);}
    protected function tearDown():void{$this->remove($this->root);}

    public function testApprovalAcknowledgesEveryNumberedLineWithoutGrantingExecution():void{$s=new ImperatorPlanningDossierReviewService($this->root);$r=$s->review($this->dossier['dossier_id'],$this->authority(),'APPROVE_DOSSIER',[],'I approve every disclosed line in this exact dossier version.',true,new \DateTimeImmutable('2026-08-23T17:10:00+00:00'));self::assertSame($r,$s->review($this->dossier['dossier_id'],$this->authority(),'OBJECT_RETURN_FOR_REVISION',[2],'ignored',false,new \DateTimeImmutable('2026-08-23T17:11:00+00:00')));self::assertSame('IMPERATOR_PLANNING_DOSSIER_APPROVED_PENDING_MISSION_AUTHORIZATION',$r['status']);self::assertTrue($r['all_lines_acknowledged']);self::assertTrue($r['mission_authorization_derivation_authority']['derivation_authority']);self::assertNull($r['curia_revision_authority']);foreach(['resource_authority','model_binding_authority','model_assignment_authority','profile_mutation_authority','credential_release_authority','provider_invocation_authority','deployment_authority','execution_authority']as$a)self::assertFalse($r[$a]);}
    public function testObjectionCitesPlainLineNumbersAndExactLineDigests():void{$r=(new ImperatorPlanningDossierReviewService($this->root))->review($this->dossier['dossier_id'],$this->authority(),'OBJECT_RETURN_FOR_REVISION',[3,2],'I object to lines 2 and 3: justify or reduce the proposed model cost.',false,new \DateTimeImmutable('2026-08-23T17:10:00+00:00'));self::assertSame('IMPERATOR_PLANNING_DOSSIER_OBJECTED_PENDING_CURIA_REVISION',$r['status']);self::assertSame([2,3],array_keys($r['cited_lines']));self::assertSame($this->dossier['lines'][1]['line_digest'],$r['cited_lines'][2]['line_digest']);self::assertTrue($r['curia_revision_authority']['revision_authority']);self::assertNull($r['mission_authorization_derivation_authority']);self::assertFalse($r['dossier_approval']);self::assertFalse($r['execution_authority']);}

    private function authority():string{return$this->dossier['imperator_review_authority']['authority_id'];}private function digest(array$r):array{$r['record_digest']=hash('sha256',CanonicalJson::encode($r));return$r;}private function write(string$p,array$r):void{if(!is_dir(dirname($p)))mkdir(dirname($p),0770,true);file_put_contents($p,json_encode($r,JSON_THROW_ON_ERROR));}
    private function remove(string$p):void{if(!is_dir($p))return;foreach(array_diff(scandir($p)?:[],['.','..'])as$e){$c=$p.'/'.$e;is_dir($c)?$this->remove($c):unlink($c);}rmdir($p);}
}
