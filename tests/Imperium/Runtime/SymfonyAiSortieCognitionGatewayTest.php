<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\SortieManifest;
use App\Imperium\Runtime\Sortie\GovernedSortieToolRegistry;
use App\Imperium\Runtime\Sortie\SortieCognitionAuthority;
use App\Imperium\Runtime\Sortie\SortieCognitionProviderInvoker;
use App\Imperium\Runtime\Sortie\SortieToolEvidence;
use App\Imperium\Runtime\Sortie\SortieToolExecutor;
use App\Imperium\Runtime\Sortie\SymfonyAiSortieCognitionGateway;
use PHPUnit\Framework\TestCase;

final class SymfonyAiSortieCognitionGatewayTest extends TestCase
{
    public function testInvokesClaimBoundProviderOnceAndReturnsPayload(): void
    {
        $invoker = $this->createMock(SortieCognitionProviderInvoker::class);
        $invoker->expects(self::once())->method('invoke')
            ->with(self::isInstanceOf(SortieCognitionAuthority::class), self::stringContains('Objective:'))
            ->willReturn('external cognition result');

        $result = (new SymfonyAiSortieCognitionGateway($invoker, new GovernedSortieToolRegistry([])))->execute($this->manifest());

        self::assertSame('external cognition result', $result->content);
        self::assertSame(['provider.deepseek'], $result->sourceIds);
        self::assertSame([], $result->toolIds);
        self::assertSame([], $result->capabilityIds);
    }

    public function testAuthorityBindsExactManifestLineageAndScope(): void
    {
        $manifest = $this->manifest(['http.get'], ['cap-http-1']);
        $authority = SortieCognitionAuthority::fromManifest($manifest);

        self::assertSame('la-cortine.sortie-cognition/v1', $authority->payload()['type']);
        self::assertSame($manifest->authorizationId, $authority->authorizationId);
        self::assertSame($manifest->contextDigest, $authority->contextDigest);
        self::assertSame(['http.get'], $authority->toolIds);
        self::assertSame(hash('sha256', \App\Bootstrap\CanonicalJson::encode($authority->payload())), $authority->digest);
    }

    public function testToolBearingSortieCapturesRawEvidenceSeparatelyFromInterpretation(): void
    {
        $manifest = $this->manifest(['http.get'], ['cap-http-1']);
        $tool = $this->createMock(SortieToolExecutor::class);
        $tool->expects(self::once())->method('supports')->with('http.get')->willReturn(true);
        $tool->expects(self::once())->method('execute')->with($manifest)->willReturn($this->evidence());
        $invoker = $this->createMock(SortieCognitionProviderInvoker::class);
        $invoker->expects(self::once())->method('invoke')->willReturn('interpreted result');

        $result = (new SymfonyAiSortieCognitionGateway($invoker, new GovernedSortieToolRegistry([$tool])))->execute($manifest);
        $decoded = json_decode($result->content, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('RAW_EXTERNAL_BYTES', $decoded['evidence']['content']);
        self::assertSame(hash('sha256', 'RAW_EXTERNAL_BYTES'), $decoded['evidence']['sha256']);
        self::assertSame('interpreted result', $decoded['interpretation']);
        self::assertSame(['https://example.test', 'provider.deepseek'], $result->sourceIds);
        self::assertSame(['http.get'], $result->toolIds);
        self::assertSame(['cap-http-1'], $result->capabilityIds);
    }

    public function testModelCannotManufactureRuntimeProvenanceInsideInterpretation(): void
    {
        $manifest = $this->manifest(['http.get'], ['cap-http-1']);
        $tool = $this->createMock(SortieToolExecutor::class);
        $tool->expects(self::once())->method('supports')->with('http.get')->willReturn(true);
        $tool->expects(self::once())->method('execute')->with($manifest)->willReturn($this->evidence());
        $invoker = $this->createMock(SortieCognitionProviderInvoker::class);
        $invoker->expects(self::once())->method('invoke')
            ->with(self::isInstanceOf(SortieCognitionAuthority::class), self::stringContains('BEGIN RAW EVIDENCE'))
            ->willReturn('{"page_title":"Example Domain","provenance":{"sha256":"invented"},"artifact_id":"fake","nested":{"source_id":"fake","claim":"keep me"}}');

        $outer = json_decode((new SymfonyAiSortieCognitionGateway($invoker, new GovernedSortieToolRegistry([$tool])))->execute($manifest)->content, true, 512, JSON_THROW_ON_ERROR);
        $interpretation = json_decode($outer['interpretation'], true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('Example Domain', $interpretation['page_title']);
        self::assertSame('keep me', $interpretation['nested']['claim']);
        self::assertArrayNotHasKey('provenance', $interpretation);
        self::assertArrayNotHasKey('artifact_id', $interpretation);
        self::assertArrayNotHasKey('source_id', $interpretation['nested']);
    }

    public function testRefusesUnavailableGovernedToolBeforeCognition(): void
    {
        $invoker = $this->createMock(SortieCognitionProviderInvoker::class);
        $invoker->expects(self::never())->method('invoke');
        $tool = $this->createMock(SortieToolExecutor::class);
        $tool->expects(self::once())->method('supports')->with('http.post')->willReturn(false);
        $gateway = new SymfonyAiSortieCognitionGateway($invoker, new GovernedSortieToolRegistry([$tool]));

        $this->expectExceptionMessage('SORTIE_TOOL_UNAVAILABLE');
        $gateway->execute($this->manifest(['http.post'], ['cap-http-1']));
    }

    public function testRefusesMultipleToolScopeBeforeRegistryDispatch(): void
    {
        $invoker = $this->createMock(SortieCognitionProviderInvoker::class);
        $invoker->expects(self::never())->method('invoke');

        $this->expectExceptionMessage('SORTIE_AI_TOOL_SCOPE_UNSUPPORTED');
        (new SymfonyAiSortieCognitionGateway($invoker, new GovernedSortieToolRegistry([])))
            ->execute($this->manifest(['http.get', 'other.tool'], ['cap-http-1']));
    }

    private function evidence(): SortieToolEvidence
    {
        return new SortieToolEvidence('RAW_EXTERNAL_BYTES', hash('sha256', 'RAW_EXTERNAL_BYTES'), 'https://example.test', 'http.get', 'cap-http-1', new \DateTimeImmutable('2026-08-10T12:00:00+00:00'));
    }

    private function manifest(array $toolIds = [], array $capabilityIds = []): SortieManifest
    {
        return new SortieManifest('execution-1', 'sortie-1', 'manifestation-1', 'commission-1', 'authorization-1', 'Summarize the supplied external observation.', str_repeat('a', 64), ['https://example.test'], $toolIds, $capabilityIds, 'return-contract/v1', new \DateTimeImmutable('+5 minutes'));
    }
}
