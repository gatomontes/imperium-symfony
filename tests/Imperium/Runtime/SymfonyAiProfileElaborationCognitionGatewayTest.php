<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Laboratorium\SymfonyAiProfileElaborationCognitionGateway;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Result\TextResult;

final class SymfonyAiProfileElaborationCognitionGatewayTest extends TestCase
{
    public function testAlchemistReturnsExactCompleteElaborationContract(): void
    {
        $agent = $this->createMock(AgentInterface::class);
        $agent->expects(self::once())->method('call')->willReturn(new TextResult(json_encode($this->elaboration(), JSON_THROW_ON_ERROR)));
        $result = (new SymfonyAiProfileElaborationCognitionGateway($agent))->elaborate(['profile_scope' => ['objective' => 'Assess']], ['limitations' => 'Passive only.']);
        self::assertSame('PROFILE_ELABORATION_COMPLETE', $result['disposition']);
        self::assertSame(['Preserve evidence.'], $result['evidence_discipline']);
    }

    public function testAlchemistGatewayRefusesMechanicalEnvelopeWithoutCognitiveContent(): void
    {
        $agent = $this->createStub(AgentInterface::class);
        $agent->method('call')->willReturn(new TextResult('{"disposition":"PROFILE_ELABORATION_COMPLETE","operating_posture":"Passive"}'));
        $this->expectExceptionMessage('L41_PROFILE_ELABORATION_CONTRACT_INVALID');
        (new SymfonyAiProfileElaborationCognitionGateway($agent))->elaborate([], []);
    }

    public function testAlchemistGatewayRefusesEmptyCognitiveField(): void
    {
        $payload = $this->elaboration(); $payload['failure_behavior'] = [];
        $agent = $this->createStub(AgentInterface::class);
        $agent->method('call')->willReturn(new TextResult(json_encode($payload, JSON_THROW_ON_ERROR)));
        $this->expectExceptionMessage('L41_PROFILE_ELABORATION_CONTRACT_INVALID');
        (new SymfonyAiProfileElaborationCognitionGateway($agent))->elaborate([], []);
    }

    public function testAlchemistGatewayRefusesAttemptToRewriteAuthoritativeScope(): void
    {
        $payload = $this->elaboration(); $payload['scope'] = ['Expanded target'];
        $agent = $this->createStub(AgentInterface::class);
        $agent->method('call')->willReturn(new TextResult(json_encode($payload, JSON_THROW_ON_ERROR)));
        $this->expectExceptionMessage('L41_PROFILE_ELABORATION_CONTRACT_INVALID');
        (new SymfonyAiProfileElaborationCognitionGateway($agent))->elaborate([], []);
    }

    private function elaboration(): array
    {
        return [
            'disposition' => 'PROFILE_ELABORATION_COMPLETE',
            'operating_posture' => 'Bounded and evidence-first.',
            'responsibilities' => ['Perform the exact mission.'],
            'non_responsibilities' => ['Do not expand scope.'],
            'reasoning_priorities' => ['Prefer attributable evidence.'],
            'evidence_discipline' => ['Preserve evidence.'],
            'tool_use_directives' => ['Use only authorized tools.'],
            'input_handling' => ['Reject out-of-scope inputs.'],
            'output_contract' => ['Produce the authorized deliverable.'],
            'escalation_conditions' => ['Escalate authority ambiguity.'],
            'uncertainty_behavior' => ['Disclose uncertainty.'],
            'failure_behavior' => ['Stop safely.'],
            'persona_adaptations' => ['Apply identity without mutation.'],
        ];
    }
}
