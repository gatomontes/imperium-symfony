<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Senate\SymfonyAiProfileExaminationReconciliationCognitionGateway;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Result\TextResult;

final class SymfonyAiProfileExaminationReconciliationCognitionGatewayTest extends TestCase
{
    public function testAcceptsExactContract():void{$value=['finding_references'=>['finding:security:digest','finding:trust:digest','finding:usability:digest'],'agreements'=>['All preserve boundaries.'],'disagreements'=>[],'attribution_treatment'=>['No defect.'],'severity_treatment'=>['All NONE.'],'limitations'=>[],'uncertainties'=>[],'rationale'=>'No disagreement exists.'];self::assertSame($value,$this->gateway(json_encode($value,JSON_THROW_ON_ERROR))->reconcile([],[]));}
    public function testNormalizesMeaningEquivalentScalarListsAndWhitespace():void{$value=$this->gateway('{"finding_references":" finding:trust:digest ","agreements":" Agreement. ","disagreements":[],"attribution_treatment":" None. ","severity_treatment":" NONE. ","limitations":[],"uncertainties":[],"rationale":" Bounded. "}')->reconcile([],[]);self::assertSame(['finding:trust:digest'],$value['finding_references']);self::assertSame(['Agreement.'],$value['agreements']);self::assertSame('Bounded.',$value['rationale']);}
    public function testRejectsDispositionField():void{$this->expectExceptionMessage('S262_PROFILE_EXAMINATION_RECONCILIATION_COGNITION_INVALID: FIELDS_INVALID');$this->gateway('{"finding_references":[],"agreements":[],"disagreements":[],"attribution_treatment":[],"severity_treatment":[],"limitations":[],"uncertainties":[],"rationale":"Bounded.","disposition":"APPROVED"}')->reconcile([],[]);}
    public function testRejectsNestedDisagreement():void{$this->expectExceptionMessage('S262_PROFILE_EXAMINATION_RECONCILIATION_COGNITION_INVALID: DISAGREEMENTS_ITEM_INVALID');$this->gateway('{"finding_references":[],"agreements":[],"disagreements":[{"finding":"unsafe"}],"attribution_treatment":[],"severity_treatment":[],"limitations":[],"uncertainties":[],"rationale":"Bounded."}')->reconcile([],[]);}
    public function testRejectsInvalidJson():void{$this->expectExceptionMessage('S262_PROFILE_EXAMINATION_RECONCILIATION_COGNITION_INVALID: JSON_INVALID');$this->gateway('Reconciliation complete.')->reconcile([],[]);}
    private function gateway(string $response):SymfonyAiProfileExaminationReconciliationCognitionGateway{$agent=$this->createStub(AgentInterface::class);$agent->method('call')->willReturn(new TextResult($response));return new SymfonyAiProfileExaminationReconciliationCognitionGateway($agent);}
}
