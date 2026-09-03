<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Command\AgentMailEmailSendCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class GovernedToolProviderSeparationBatch8Test extends TestCase
{
    public function testRetiredLiveCommandFailsClosedWithoutSelfAssembledAuthority(): void
    {
        $root = sys_get_temp_dir().'/retired-command-'.bin2hex(random_bytes(6));
        mkdir($root);
        $reader = new \App\Imperium\Runtime\ProviderTransition\NativeBindingReader(new \App\Imperium\Runtime\ProviderTransition\NativeState($root));
        $consumer = new \App\Imperium\Runtime\Clavium\DeterministicJournalBoundCredentialBroker($root,
            new \App\Imperium\Runtime\LaCortine\EnvironmentCredentialBroker(),
            new \App\Imperium\Runtime\LaCortine\AgentMailIdempotencyHeaderAdapter($reader), $reader);
        $tester = new CommandTester(new AgentMailEmailSendCommand($consumer, new \Symfony\Component\Clock\NativeClock()));
        rmdir($root);

        self::assertSame(1, $tester->execute([]));
        self::assertStringContainsString('REFUSED GOVERNED_EMAIL_SEND_EXECUTOR_UNAVAILABLE', $tester->getDisplay());
    }

    public function testCommandCannotIssueCredentialsSelectProviderOrPerformIo(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 3).'/src/Command/AgentMailEmailSendCommand.php');

        foreach (['->consume(', '->invoke(', '->issue(', 'random_bytes(', 'AgentMailEmailTransport', 'DeterministicBoundaryExecutor', 'file_get_contents(', 'AGENTMAIL_API_KEY', 'AGENTMAIL_INBOX_ID'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
        self::assertStringContainsString('->inspectClaim(', $source);
    }

    public function testBatchEightRecordsTheExactActivationAndCustodyBlockers(): void
    {
        $root = dirname(__DIR__, 3);
        $handoff = (string) file_get_contents($root.'/docs/handoffs/governed-tool-provider-separation-batch-8-complete.md');

        foreach (['`BOUND_INACTIVE`', 'deny external I/O', 'issuing process', 'Only Batch 9 may next be considered', 'Batch 9 is not authorized'] as $proof) {
            self::assertStringContainsString($proof, $handoff);
        }
    }
}
