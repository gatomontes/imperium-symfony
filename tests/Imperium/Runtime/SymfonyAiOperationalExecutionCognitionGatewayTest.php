<?php
declare(strict_types=1);namespace App\Tests\Imperium\Runtime;
use App\Imperium\Runtime\Mission\SymfonyAiOperationalExecutionCognitionGateway;use PHPUnit\Framework\TestCase;use Symfony\AI\Agent\AgentInterface;use Symfony\AI\Platform\Result\TextResult;
final class SymfonyAiOperationalExecutionCognitionGatewayTest extends TestCase
{
 public function testAcceptsExactCompletedContract():void{$r=['disposition'=>'COMPLETED','output'=>'Bounded output.','evidence_claims'=>['Exact input supplied.'],'uncertainties'=>[],'stop_condition_triggered'=>false,'stop_rationale'=>'No stop condition triggered.'];self::assertSame($r,$this->gateway(json_encode($r,JSON_THROW_ON_ERROR))->execute([],[]));}
 public function testRefusesInconsistentStopDisposition():void{$this->expectExceptionMessage('M209_OPERATIONAL_EXECUTION_COGNITION_INVALID: CONTRACT_INVALID');$this->gateway('{"disposition":"COMPLETED","output":"Bounded.","evidence_claims":[],"uncertainties":[],"stop_condition_triggered":true,"stop_rationale":"Stopped."}')->execute([],[]);}
 private function gateway(string$r):SymfonyAiOperationalExecutionCognitionGateway{$a=$this->createStub(AgentInterface::class);$a->method('call')->willReturn(new TextResult($r));return new SymfonyAiOperationalExecutionCognitionGateway($a);}
}
