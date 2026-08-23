<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Foundry\FoundryProvisioningCaseService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:mastermason:prepare-foundry', description: 'Open a non-authorizing Foundry provisioning case')]
final class MasterMasonPrepareFoundryCommand extends Command
{
    public function __construct(private readonly FoundryProvisioningCaseService $service) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('demand-id', InputArgument::REQUIRED, 'Exact Foundry activation demand'); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try { $case = $this->service->open((string) $input->getArgument('demand-id')); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        $output->writeln('<info>FOUNDRY_PROVISIONING_OPENED</info> '.$case['case_id']);
        $output->writeln('Seat: '.$case['seat'].' CANONICAL_STAFF_READY');
        $output->writeln('Status: '.$case['status']);
        $output->writeln('Construction authority: PRESENT FOR EXACT DEMANDS; NOT EXERCISABLE WHILE VACANT');
        $output->writeln('Mission Persona selection: NOT REQUIRED');
        $output->writeln('Per-mission Profile derivation: NOT REQUIRED');
        $output->writeln('Commission authority: NOT GRANTED');
        $output->writeln('Spawning authority: NOT GRANTED');
        $output->writeln('Seat binding authority: NOT GRANTED');
        $output->writeln('Recipient acceptance: NOT RECORDED');
        $output->writeln('Execution authority: NOT GRANTED');
        return self::SUCCESS;
    }
}
