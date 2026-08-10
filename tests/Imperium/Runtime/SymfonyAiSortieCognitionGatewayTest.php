<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\SortieManifest;
use App\Imperium\Runtime\Sortie\SortieToolEvidence;
use App\Imperium\Runtime\Sortie\SortieToolExecutor;
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
        $tool = $this->createMock(SortieToolExecutor::class);
        $tool->expects(self::never())->method('execute');

        $gateway = new SymfonyAiSortieCognitionGateway($agent, $tool);
        $result = $gateway->execute($this->manifest());

        self::assertSame('external cognition result', $result->content);
        self::assertSame(['ai.platform.generic.deepseek'], $result->sourceIds);
        self::assertSame([], $result->toolIds);
        self::assertSame([], $result->capabilityIds);
    }

    public function testToolBearingSortieCapturesRawEvidenceSeparatelyFromInterpretation(): void
    {
        $manifest = $this->manifest(['http.get'], ['cap-http-1']);
        $evidence = new SortieToolEvidence(
            'RAW_EXTERNAL_BYTES',
            hash('sha256', 'RAW_EXTERNAL_BYTES'),
            'https://example.test',
            'http.get',
            'cap-http-1',
            new \DateTimeImmutable('2026-08-10T12:00:00+00:00'),
        );

        $tool = $this->createMock(SortieToolExecutor::class);
        $tool->expects(self::once())->method('execute')->with($manifest)->willReturn($evidence);
        $agent = $this->createMock(AgentInterface::class);
        $agent->expects(self::once())->method('call')->willReturn(new TextResult('interpreted result'));

        $result = (new SymfonyAiSortieCognitionGateway($agent, $tool))->execute($manifest);
        $decoded = json_decode($result->content, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('RAW_EXTERNAL_BYTES', $decoded['evidence']['content']);
        self::assertSame(hash('sha256', 'RAW_EXTERNAL_BYTES'), $decoded['evidence']['sha256']);
        self::assertSame('interpreted result', $decoded['interpretation']);
        self::assertSame(['https://example.test', 'ai.platform.generic.deepseek'], $result->sourceIds);
        self::assertSame(['http.get'], $result->toolIds);
        self::assertSame(['cap-http-1'], $result->capabilityIds);
    }

    public function testRefusesUnsupportedToolScopeBeforeCognition(): void
    {
        $agent = $this->createMock(AgentInterface::class);
        $agent->expects(self::never())->method('call');
        $tool = $this->createMock(SortieToolExecutor::class);
        $tool->expects(self::never())->method('execute');
        $gateway = new SymfonyAiSortieCognitionGateway($agent, $tool);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SORTIE_AI_TOOL_SCOPE_UNSUPPORTED');
        $gateway->execute($this->manifest(['http.post'], ['cap-http-1']));
    }

    /** @param list<string> $toolIds @param list<string> $capabilityIds */
    private function manifest(array $toolIds = [], array $capabilityIds = []): SortieManifest
    {
        return new SortieManifest(
            'execution-1',
            'sortie-1',
            'manifestation-1',
            'commission-1',
            'authorization-1',
            'Summarize the supplied external observation.',
            str_repeat('a', 64),
            ['https://example.test'],
            $toolIds,
            $capabilityIds,
            'return-contract/v1',
            new \DateTimeImmutable('+5 minutes'),
        );
    }
}
