<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Garrison\ConstableConstructionCommissionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:mastermason:commission-constable', description: 'Issue the exact canonical Constable construction commission to Conscription')]
final class MasterMasonCommissionConstableCommand extends Command
{
    public function __construct(private readonly ConstableConstructionCommissionService $service) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('provisioning-case-id', InputArgument::REQUIRED, 'Exact ready Constable provisioning case')->addOption('json', null, InputOption::VALUE_NONE, 'Emit complete construction commission'); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try { $commission = $this->service->issue((string) $input->getArgument('provisioning-case-id')); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        if ((bool) $input->getOption('json')) { $output->writeln(json_encode($commission, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)); return self::SUCCESS; }
        $output->writeln('<info>CONSTABLE_CONSTRUCTION_COMMISSION_ISSUED</info> '.$commission['commission_id']);
        $output->writeln('Target: '.$commission['target_seat']);
        $output->writeln('Status: '.$commission['status']);
        $output->writeln('Spawning authority: GRANTED FOR EXACT COMMISSION');
        $output->writeln('Seat binding authority: NOT GRANTED');
        $output->writeln('Inventory response authority: NOT GRANTED');
        $output->writeln('Execution authority: NOT GRANTED');
        return self::SUCCESS;
    }
}
