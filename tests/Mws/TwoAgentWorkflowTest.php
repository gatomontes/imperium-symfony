<?php

namespace App\Tests\Mws;

use App\Mws\AgentResponder;
use App\Mws\TwoAgentWorkflow;
use PHPUnit\Framework\TestCase;

final class TwoAgentWorkflowTest extends TestCase
{
    public function testThreeAttributedRepliesInOrder(): void
    {
        $fake = new class implements AgentResponder {
            public array $roles = [];

            public function reply(string $role, string $instruction): string
            {
                $this->roles[] = $role;

                return 'Response '.count($this->roles);
            }
        };

        $result = (new TwoAgentWorkflow())->run('Test task', $fake);

        self::assertSame('completed', $result['status']);
        self::assertSame(['coordinator', 'worker', 'coordinator'], $fake->roles);
        self::assertSame(['operator', 'coordinator', 'worker', 'coordinator'], array_column($result['messages'], 'role'));
        self::assertSame('Response 3', $result['messages'][3]['text']);
    }

    public function testNoReplyIsNeverRecordedAsSuccessful(): void
    {
        $fake = new class implements AgentResponder {
            public function reply(string $role, string $instruction): string
            {
                return '';
            }
        };
        $result = (new TwoAgentWorkflow())->run('task', $fake);
        self::assertSame('failed', $result['status']);
        self::assertCount(1, $result['messages']);
    }

    public function testEmptyTaskIsRejectedBeforeProviderCall(): void
    {
        $fake = new class implements AgentResponder {
            public function reply(string $role, string $instruction): string
            {
                throw new \LogicException('Should not call provider');
            }
        };
        $this->expectException(\InvalidArgumentException::class);
        (new TwoAgentWorkflow())->run('  ', $fake);
    }
}
