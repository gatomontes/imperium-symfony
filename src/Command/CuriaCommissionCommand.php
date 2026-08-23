<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Curia\CommissioningService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:curia:commission', description: 'Issue bounded planning commissions from an approved and authorized structured Mission Plan')]
final class CuriaCommissionCommand extends Command
{
    public function __construct(private readonly CommissioningService $commissioning)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('proceeding-id', InputArgument::REQUIRED)
            ->addArgument('turn', InputArgument::REQUIRED)
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit complete sealed commission packets');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $result = $this->commissioning->issue(
                (string) $input->getArgument('proceeding-id'),
                (int) $input->getArgument('turn'),
            );
        } catch (\Throwable $exception) {
            $output->writeln('<error>REFUSED</error> '.$exception->getMessage());

            return self::FAILURE;
        }
        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }
        $output->writeln('<info>PLANNING_COMMISSIONS_ISSUED</info>');
        foreach ($result['commissions'] as $name => $commission) {
            $output->writeln(sprintf('%s %s %s', ucfirst($name), $commission['commission_id'], $commission['status']));
        }
        foreach ($result['mechanical_support'] as $resource) {
            $output->writeln('Mechanical support: '.$resource);
        }
        $output->writeln('Execution authority: NOT GRANTED');

        return self::SUCCESS;
    }
}
