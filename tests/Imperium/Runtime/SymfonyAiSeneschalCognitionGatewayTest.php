<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Curia\SymfonyAiSeneschalCognitionGateway;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Result\TextResult;

final class SymfonyAiSeneschalCognitionGatewayTest extends TestCase
{
    public function testAcceptsExactBoundedDisposition(): void
    {
        $agent = $this->createMock(AgentInterface::class);
        $agent->expects(self::once())->method('call')->willReturn(new TextResult(json_encode([
            'disposition' => 'CLARIFICATION_REQUIRED',
            'decision' => 'The requested outcome is not yet concrete.',
            'question' => 'What exact artifact must this mission deliver?',
            'resource_demands' => [],
            'authorization_required' => false,
            'mission_plan' => null,
        ], JSON_THROW_ON_ERROR)));

        $decision = (new SymfonyAiSeneschalCognitionGateway($agent))->decide('Assess security.', [
            'instance_id' => 'imperium-test',
            'proceeding_id' => 'proceeding-test',
        ]);

        self::assertSame('CLARIFICATION_REQUIRED', $decision['disposition']);
        self::assertSame('What exact artifact must this mission deliver?', $decision['question']);
    }

    public function testRefusesMalformedDisposition(): void
    {
        $agent = $this->createStub(AgentInterface::class);
        $agent->method('call')->willReturn(new TextResult('{"decision":"do it"}'));

        $this->expectExceptionMessage('C11_SENESCHAL_CONTRACT_INVALID');
        (new SymfonyAiSeneschalCognitionGateway($agent))->decide('Do it.', []);
    }

    public function testDraftPlanMayCarryUnresolvedAuthorizationDemand(): void
    {
        $agent = $this->createStub(AgentInterface::class);
        $agent->method('call')->willReturn(new TextResult(json_encode([
            'disposition' => 'MISSION_PLAN_DRAFTED',
            'decision' => 'Draft a passive assessment, pending authorization for external research.',
            'question' => null,
            'resource_demands' => ['external research'],
            'authorization_required' => true,
            'mission_plan' => $this->missionPlan(),
        ], JSON_THROW_ON_ERROR)));

        $decision = (new SymfonyAiSeneschalCognitionGateway($agent))->advance(
            ['imperator_request' => ['content' => 'Prepare a cybersecurity assessment mission.']],
            [],
            'Draft the plan but do not begin research.',
            ['proceeding_id' => 'proceeding-test'],
        );

        self::assertSame('MISSION_PLAN_DRAFTED', $decision['disposition']);
        self::assertTrue($decision['authorization_required']);
    }

    public function testRefusesLegacyProfessionSelectingMissionPlanField(): void
    {
        $agent = $this->createStub(AgentInterface::class);
        $plan = $this->missionPlan();
        $plan['personnel_requirements'] = ['Security assessor'];
        unset($plan['capability_requirements']);
        $agent->method('call')->willReturn(new TextResult(json_encode([
            'disposition' => 'MISSION_PLAN_DRAFTED',
            'decision' => 'Select a named profession in Curia.',
            'question' => null,
            'resource_demands' => [],
            'authorization_required' => false,
            'mission_plan' => $plan,
        ], JSON_THROW_ON_ERROR)));

        $this->expectExceptionMessage('C12_MISSION_PLAN_INVALID');
        (new SymfonyAiSeneschalCognitionGateway($agent))->advance(
            ['imperator_request' => ['content' => 'Prepare a mission.']],
            [],
            'Draft it.',
            ['proceeding_id' => 'proceeding-test'],
        );
    }

    private function missionPlan(): array
    {
        return [
            'objective' => 'Assess the public web application.',
            'scope' => ['Explicitly supplied public URLs'],
            'deliverables' => ['Prioritized risk report'],
            'constraints' => ['Passive and non-invasive only'],
            'required_inputs' => ['Target URLs and scope definition'],
            'capability_requirements' => ['Analyze publicly observable application behavior', 'Distinguish evidence from inference', 'Independently challenge security findings'],
            'tool_requirements' => ['Approved passive review tooling or manual checklist'],
            'data_requirements' => ['Publicly observable application responses'],
            'office_participation' => ['Guildhall personnel disposition', 'Armory tooling disposition'],
            'stop_conditions' => ['Any need for authentication or active scanning'],
        ];
    }
}
