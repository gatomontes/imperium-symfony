<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Garrison\ConstableProvisioningService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:mastermason:prepare-constable', description: 'Open the canonical resident Constable provisioning case')]
final class MasterMasonPrepareConstableCommand extends Command
{
    public function __construct(private readonly ConstableProvisioningService $service) { parent::__construct(); }

    protected function configure(): void
    {
        $this->addArgument('inquiry-id', InputArgument::REQUIRED, 'Exact vacancy-blocked Garrison inquiry')->addOption('json', null, InputOption::VALUE_NONE, 'Emit complete provisioning case');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try { $case = $this->service->open((string) $input->getArgument('inquiry-id')); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        if ((bool) $input->getOption('json')) { $output->writeln(json_encode($case, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)); return self::SUCCESS; }
        $output->writeln('<info>CONSTABLE_PROVISIONING_OPENED</info> '.$case['case_id']);
        $output->writeln('Seat: '.$case['target_seat']);
        $output->writeln('Status: '.$case['status']);
        $output->writeln('Mission Persona selection: NOT REQUIRED');
        $output->writeln('Spawning authority: NOT GRANTED');
        $output->writeln('Inventory response authority: NOT GRANTED');
        $output->writeln('Execution authority: NOT GRANTED');
        return self::SUCCESS;
    }
}
