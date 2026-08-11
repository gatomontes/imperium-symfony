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
}
