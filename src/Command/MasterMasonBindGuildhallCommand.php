<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Guildhall\GuildhallSeatBindingService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:mastermason:bind-guildhall', description: 'Atomically bind the four qualified Guildhall manifestations to their Seats')]
final class MasterMasonBindGuildhallCommand extends Command
{
    public function __construct(private readonly GuildhallSeatBindingService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('summons-id', InputArgument::REQUIRED, 'Exact fulfilled Guildhall summons identifier')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the complete atomic binding cohort');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $cohort = $this->service->bind((string) $input->getArgument('summons-id'));
        } catch (\Throwable $exception) {
            $output->writeln('<error>REFUSED</error> '.$exception->getMessage());

            return self::FAILURE;
        }
        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($cohort, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }
        $output->writeln('<info>GUILDHALL_SEATS_BOUND_ATOMICALLY</info> '.$cohort['binding_id']);
        foreach ($cohort['bindings'] as $binding) {
            $output->writeln('- '.$binding['seat'].' '.$binding['status']);
        }
        $output->writeln('Binding atomic: YES');
        $output->writeln('Recipient acceptance: NOT RECORDED');
        $output->writeln('Execution authority: NOT GRANTED');

        return self::SUCCESS;
    }
}
