<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Audit\DelegateMissionOperationalEvidenceAuditService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:delegate:audit-operational-evidence', aliases: ['imperium:delegate:audit-terminal'], description: 'Verify the fourteen-record terminal operational-evidence subchain without mutation')]
final class DelegateMissionAuditTerminalCommand extends Command
{
    public function __construct(private readonly DelegateMissionOperationalEvidenceAuditService $audit) { parent::__construct(); }

    protected function configure(): void
    {
        $this->addArgument('terminal-id', InputArgument::REQUIRED)->addOption('json', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $result = $this->audit->audit((string) $input->getArgument('terminal-id'));
        } catch (\Throwable $exception) {
            $output->writeln('<error>REFUSED</error> '.$exception->getMessage());
            return self::FAILURE;
        }
        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            return self::SUCCESS;
        }
        $output->writeln('<info>VALID</info> '.$result['terminal_id']);
        $output->writeln('Checkpoint: '.$result['terminal_checkpoint']);
        $output->writeln('Verified records: '.$result['verified_records']);
        $output->writeln('Scope: '.$result['completeness_claim']);
        $output->writeln('Continuing authority: false');
        return self::SUCCESS;
    }
}
