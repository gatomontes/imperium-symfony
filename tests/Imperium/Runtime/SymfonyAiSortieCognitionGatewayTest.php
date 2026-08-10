<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\SortieManifest;
use App\Imperium\Runtime\Sortie\SymfonyAiSortieCognitionGateway;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Result\TextResult;

final class SymfonyAiSortieCognitionGatewayTest extends TestCase
{
    public function testInvokesConfiguredAgentOnceAndReturnsProviderPayload(): void
    {
        $agent = $this->createMock(AgentInterface::class);
        $agent->expects(self::once())
            ->method('call')
            ->willReturn(new TextResult('external cognition result'));

        $gateway = new SymfonyAiSortieCognitionGateway($agent);
        $result = $gateway->execute($this->manifest());

        self::assertSame('external cognition result', $result->content);
        self::assertSame(['ai.platform.openai'], $result->sourceIds);
        self::assertSame([], $result->toolIds);
        self::assertSame([], $result->capabilityIds);
    }

    public function testRefusesToolBearingManifestUntilToolBoundaryIsExplicitlyBound(): void
    {
        $agent = $this->createMock(AgentInterface::class);
        $agent->expects(self::never())->method('call');
        $gateway = new SymfonyAiSortieCognitionGateway($agent);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SORTIE_AI_TOOLS_UNBOUND');
        $gateway->execute($this->manifest(['http.get']));
    }

    /** @param list<string> $toolIds */
    private function manifest(array $toolIds = []): SortieManifest
    {
        return new SortieManifest(
            'sortie-1',
            'manifestation-1',
            'execution-1',
            'commission-1',
            'authorization-1',
            'Summarize the supplied external observation.',
            str_repeat('a', 64),
            ['https://example.test'],
            $toolIds,
            [],
            'return-contract/v1',
            new \DateTimeImmutable('+5 minutes'),
        );
    }
}
