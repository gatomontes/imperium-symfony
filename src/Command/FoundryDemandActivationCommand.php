<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Foundry\FoundryActivationDemandService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:foundry:demand-activation', description: 'Record Foundry runtime prerequisites for a delivered construction authorization')]
final class FoundryDemandActivationCommand extends Command
{
    public function __construct(private readonly FoundryActivationDemandService $service) { parent::__construct(); }

    protected function configure(): void
    {
        $this->addArgument('delivery-id', InputArgument::REQUIRED, 'Exact Foundry construction-authorization delivery')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the complete activation demand');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try { $demand = $this->service->demand((string) $input->getArgument('delivery-id')); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($demand, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            return self::SUCCESS;
        }
        $output->writeln('<info>FOUNDRY_ACTIVATION_REQUIRED</info> '.$demand['demand_id']);
        foreach ($demand['required_seats'] as $seat) $output->writeln('- '.$seat['seat'].' '.$seat['status']);
        $output->writeln('Status: '.$demand['status']);
        $output->writeln('Construction authority: PRESENT FOR EXACT DEMANDS; NOT EXERCISABLE WHILE VACANT');
        $output->writeln('Mission Persona selection: NOT REQUIRED');
        $output->writeln('Spawning authority: NOT GRANTED');
        $output->writeln('Seat binding authority: NOT GRANTED');
        $output->writeln('Recipient acceptance: NOT RECORDED');
        $output->writeln('Execution authority: NOT GRANTED');
        return self::SUCCESS;
    }
}
