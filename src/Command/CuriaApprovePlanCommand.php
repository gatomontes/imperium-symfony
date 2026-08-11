<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Curia\ImperatorActs;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:curia:approve-plan', description: 'Approve one exact drafted Mission Plan without granting resource authority')]
final class CuriaApprovePlanCommand extends Command
{
    public function __construct(private readonly ImperatorActs $acts)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('proceeding-id', InputArgument::REQUIRED)
            ->addArgument('turn', InputArgument::REQUIRED)
            ->addOption('act-id', null, InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $act = $this->acts->approvePlan(
                (string) $input->getArgument('proceeding-id'),
                (int) $input->getArgument('turn'),
                is_string($input->getOption('act-id')) ? $input->getOption('act-id') : null,
            );
        } catch (\Throwable $exception) {
            $output->writeln('<error>REFUSED</error> '.$exception->getMessage());

            return self::FAILURE;
        }
        $output->writeln('<info>PLAN_APPROVED</info> '.$act['act_id']);
        $output->writeln('Resource authority: NOT GRANTED');
        $output->writeln('Commissioning ready: '.($act['readiness']['commissioning_ready'] ? 'YES' : 'NO'));
        $output->writeln('Execution authority: NOT GRANTED');

        return self::SUCCESS;
    }
}
