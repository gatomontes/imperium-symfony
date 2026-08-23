<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Senate\SymfonyAiProfileExaminationFindingCognitionGateway;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Result\TextResult;

final class SymfonyAiProfileExaminationFindingCognitionGatewayTest extends TestCase
{
    public function testAcceptsExactFindingContract(): void
    {
        $finding = ['disposition'=>'PASS','attributed_defect'=>null,'evidence_references'=>['testimony:trust:digest'],'rationale'=>'Boundaries preserved.','severity'=>'NONE','limitations'=>[],'uncertainty'=>[]];
        self::assertSame($finding, $this->gateway(json_encode($finding, JSON_THROW_ON_ERROR))->find('trust', [], []));
    }

    public function testNormalizesMeaningEquivalentScalarListsWhitespaceAndEnumCase(): void
    {
        $finding = $this->gateway('{"disposition":" pass ","attributed_defect":null,"evidence_references":" testimony:trust:digest ","rationale":" Bounded. ","severity":" none ","limitations":" Supplied testimony only. ","uncertainty":[]}')->find('trust', [], []);
        self::assertSame('PASS', $finding['disposition']);
        self::assertSame(['testimony:trust:digest'], $finding['evidence_references']);
        self::assertSame('Bounded.', $finding['rationale']);
        self::assertSame('NONE', $finding['severity']);
        self::assertSame(['Supplied testimony only.'], $finding['limitations']);
    }

    public function testRefusesAdditionalFieldsWithNonSensitiveDiagnostic(): void
    {
        $this->expectExceptionMessage('S242_PROFILE_EXAMINATION_FINDING_COGNITION_INVALID: FIELDS_INVALID');
        $this->gateway('{"disposition":"PASS","attributed_defect":null,"evidence_references":[],"rationale":"Bounded.","severity":"NONE","limitations":[],"uncertainty":[],"vote":"APPROVE"}')->find('trust', [], []);
    }

    public function testRefusesNestedEvidenceReferencesWithNonSensitiveDiagnostic(): void
    {
        $this->expectExceptionMessage('S242_PROFILE_EXAMINATION_FINDING_COGNITION_INVALID: EVIDENCE_REFERENCES_ITEM_INVALID');
        $this->gateway('{"disposition":"PASS","attributed_defect":null,"evidence_references":[{"reference":"unsafe"}],"rationale":"Bounded.","severity":"NONE","limitations":[],"uncertainty":[]}')->find('trust', [], []);
    }

    public function testRefusesInvalidJsonWithNonSensitiveDiagnostic(): void
    {
        $this->expectExceptionMessage('S242_PROFILE_EXAMINATION_FINDING_COGNITION_INVALID: JSON_INVALID');
        $this->gateway('Finding: PASS')->find('trust', [], []);
    }

    public function testFindingStageUsesSharedBoundedTransientRetry(): void
    {
        $finding = ['disposition'=>'PASS','attributed_defect'=>null,'evidence_references'=>['testimony:trust:digest'],'rationale'=>'Boundaries preserved.','severity'=>'NONE','limitations'=>[],'uncertainty'=>[]];
        $agent = $this->createMock(AgentInterface::class);
        $agent->expects(self::exactly(2))->method('call')->willReturnOnConsecutiveCalls(new TextResult(''), new TextResult(json_encode($finding, JSON_THROW_ON_ERROR)));
        self::assertSame($finding, (new SymfonyAiProfileExaminationFindingCognitionGateway($agent, $agent, $agent))->find('trust', [], []));
    }

    private function gateway(string $response): SymfonyAiProfileExaminationFindingCognitionGateway
    {
        $agent = $this->createStub(AgentInterface::class);
        $agent->method('call')->willReturn(new TextResult($response));
        return new SymfonyAiProfileExaminationFindingCognitionGateway($agent, $agent, $agent);
    }
}
