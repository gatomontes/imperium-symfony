<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Curia\SymfonyAiSeneschalCognitionGateway;
use PHPUnit\Framework\TestCase;

final class SymfonyAiSeneschalCognitionGatewayTest extends TestCase
{
    public function testAcceptsExactBoundedDisposition(): void
    {
        $decision = $this->parse(json_encode([
            'disposition' => 'CLARIFICATION_REQUIRED',
            'decision' => 'The requested outcome is not yet concrete.',
            'question' => 'What exact artifact must this mission deliver?',
            'resource_demands' => [],
            'authorization_required' => false,
            'mission_plan' => null,
        ], JSON_THROW_ON_ERROR), ['ADMITTED_FOR_PLANNING', 'CLARIFICATION_REQUIRED', 'REFUSED']);

        self::assertSame('CLARIFICATION_REQUIRED', $decision['disposition']);
        self::assertSame('What exact artifact must this mission deliver?', $decision['question']);
    }

    public function testRefusesMalformedDisposition(): void
    {
        $this->expectExceptionMessage('C11_SENESCHAL_CONTRACT_INVALID');
        $this->parse('{"decision":"do it"}', ['ADMITTED_FOR_PLANNING', 'CLARIFICATION_REQUIRED', 'REFUSED']);
    }

    public function testDraftPlanMayCarryUnresolvedAuthorizationDemand(): void
    {
        $decision = $this->parse(json_encode([
            'disposition' => 'MISSION_PLAN_DRAFTED',
            'decision' => 'Draft a passive assessment, pending authorization for external research.',
            'question' => null,
            'resource_demands' => ['external research'],
            'authorization_required' => true,
            'mission_plan' => $this->missionPlan(),
        ], JSON_THROW_ON_ERROR), ['PLANNING_CONTINUES', 'CLARIFICATION_REQUIRED', 'AUTHORIZATION_REQUIRED', 'MISSION_PLAN_DRAFTED', 'REFUSED']);

        self::assertSame('MISSION_PLAN_DRAFTED', $decision['disposition']);
        self::assertTrue($decision['authorization_required']);
    }

    public function testRefusesLegacyProfessionSelectingMissionPlanField(): void
    {
        $plan = $this->missionPlan();
        $plan['personnel_requirements'] = ['Security assessor'];
        unset($plan['capability_requirements']);

        $this->expectExceptionMessage('C12_MISSION_PLAN_INVALID');
        $this->parse(json_encode([
            'disposition' => 'MISSION_PLAN_DRAFTED',
            'decision' => 'Select a named profession in Curia.',
            'question' => null,
            'resource_demands' => [],
            'authorization_required' => false,
            'mission_plan' => $plan,
        ], JSON_THROW_ON_ERROR), ['PLANNING_CONTINUES', 'CLARIFICATION_REQUIRED', 'AUTHORIZATION_REQUIRED', 'MISSION_PLAN_DRAFTED', 'REFUSED']);
    }

    private function parse(string $content, array $allowed): array
    {
        $reflection = new \ReflectionClass(SymfonyAiSeneschalCognitionGateway::class);
        $gateway = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('invoke');

        return $method->invoke($gateway, $content, $allowed);
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
