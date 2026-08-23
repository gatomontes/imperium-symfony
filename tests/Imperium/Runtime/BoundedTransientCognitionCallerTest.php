<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Cognition\BoundedTransientCognitionCaller;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Result\TextResult;

final class BoundedTransientCognitionCallerTest extends TestCase
{
    public function testRetriesOneEmptyResponseAndReturnsTheSecondResult(): void
    {
        $agent = $this->createMock(AgentInterface::class);
        $agent->expects(self::exactly(2))->method('call')->willReturnOnConsecutiveCalls(new TextResult(''), new TextResult('{"ok":true}'));
        self::assertSame('{"ok":true}', (new BoundedTransientCognitionCaller())->call($agent, 'prompt', 'STAGE_INVALID'));
    }

    public function testTwoEmptyResponsesFailClosedWithStageDiagnostic(): void
    {
        $agent = $this->createMock(AgentInterface::class);
        $agent->expects(self::exactly(2))->method('call')->willReturn(new TextResult(''));
        $this->expectExceptionMessage('STAGE_INVALID: EMPTY_RESPONSE');
        (new BoundedTransientCognitionCaller())->call($agent, 'prompt', 'STAGE_INVALID');
    }

    public function testRetriesOneProviderTimeoutAndReturnsTheSecondResult(): void
    {
        $calls = 0; $agent = $this->createMock(AgentInterface::class);
        $agent->expects(self::exactly(2))->method('call')->willReturnCallback(static function () use (&$calls): TextResult {
            if (0 === $calls++) throw new \RuntimeException('Idle timeout reached for provider.');
            return new TextResult('{"ok":true}');
        });
        self::assertSame('{"ok":true}', (new BoundedTransientCognitionCaller())->call($agent, 'prompt', 'STAGE_INVALID'));
    }

    public function testTwoProviderTimeoutsFailClosedWithStageDiagnostic(): void
    {
        $agent = $this->createMock(AgentInterface::class);
        $agent->expects(self::exactly(2))->method('call')->willThrowException(new \RuntimeException('Request timed out.'));
        $this->expectExceptionMessage('STAGE_INVALID: PROVIDER_TIMEOUT');
        (new BoundedTransientCognitionCaller())->call($agent, 'prompt', 'STAGE_INVALID');
    }

    public function testNonTransientProviderFailureIsNotRetried(): void
    {
        $agent = $this->createMock(AgentInterface::class);
        $agent->expects(self::once())->method('call')->willThrowException(new \RuntimeException('Authentication failed.'));
        $this->expectExceptionMessage('Authentication failed.');
        (new BoundedTransientCognitionCaller())->call($agent, 'prompt', 'STAGE_INVALID');
    }
}
