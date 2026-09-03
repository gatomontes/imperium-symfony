<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Clavium\DeterministicJournalBoundCredentialBroker as JournalConsumer;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:email:send-agentmail', description: 'Refuse the retired self-authorizing AgentMail command.')]
final class AgentMailEmailSendCommand extends Command
{
    public function __construct(private readonly JournalConsumer $consumer, private readonly ClockInterface $clock)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('inspect-claim', null, InputOption::VALUE_REQUIRED, 'Read the existing consumer binding interpretation; never send.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $claimId = $input->getOption('inspect-claim');
        if (is_string($claimId)) {
            try {
                $result = $this->consumer->inspectClaim($claimId, $this->clock->now());
                $output->writeln(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
                return 'COMMITTED_CURRENT' === $result['classification'] ? Command::SUCCESS : Command::FAILURE;
            } catch (\Throwable) {
                $output->writeln('REFUSED CCI_INTERPRETATION_UNAVAILABLE UNKNOWN_REPLAY_PROHIBITED');
                return Command::FAILURE;
            }
        }
        $output->writeln('REFUSED GOVERNED_EMAIL_SEND_EXECUTOR_UNAVAILABLE');
        $output->writeln('The retired command may not assemble commission, authorization, provider selection, or credential capability.');

        return Command::FAILURE;
    }
}
