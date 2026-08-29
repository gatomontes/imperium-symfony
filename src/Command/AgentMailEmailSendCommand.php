<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:email:send-agentmail', description: 'Refuse the retired self-authorizing AgentMail command.')]
final class AgentMailEmailSendCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('REFUSED GOVERNED_EMAIL_SEND_EXECUTOR_UNAVAILABLE');
        $output->writeln('The retired command may not assemble commission, authorization, provider selection, or credential capability.');

        return Command::FAILURE;
    }
}
