<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Guildhall\GuildhallActivationDemandService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:guildhall:demand-activation', description: 'Record Guildhall runtime prerequisites for a delivered planning commission')]
final class GuildhallDemandActivationCommand extends Command
{
    public function __construct(private readonly GuildhallActivationDemandService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('commission-id', InputArgument::REQUIRED, 'Exact delivered Guildhall commission identifier')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the complete activation demand');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $demand = $this->service->demand((string) $input->getArgument('commission-id'));
        } catch (\Throwable $exception) {
            $output->writeln('<error>REFUSED</error> '.$exception->getMessage());

            return self::FAILURE;
        }
        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($demand, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        $output->writeln('<info>GUILDHALL_ACTIVATION_REQUIRED</info> '.$demand['demand_id']);
        foreach ($demand['required_seats'] as $seat) {
            $output->writeln('- '.$seat['seat'].' '.$seat['profile']);
        }
        $output->writeln('Status: '.$demand['status']);
        $output->writeln('Spawning authority: NOT GRANTED');
        $output->writeln('Recipient acceptance: NOT RECORDED');
        $output->writeln('Execution authority: NOT GRANTED');

        return self::SUCCESS;
    }
}
