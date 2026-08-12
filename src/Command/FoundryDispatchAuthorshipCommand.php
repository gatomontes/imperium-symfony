<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Foundry\SpecializedAuthorshipCommissionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:foundry:dispatch-authorship', description: 'Dispatch exact Hagiography and Studium authorship commissions for one Persona production case')]
final class FoundryDispatchAuthorshipCommand extends Command
{
    public function __construct(private readonly SpecializedAuthorshipCommissionService $service) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('case-id', InputArgument::REQUIRED, 'Exact Persona production case')->addOption('json', null, InputOption::VALUE_NONE, 'Emit complete commission records'); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try { $result = $this->service->dispatch((string) $input->getArgument('case-id')); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        if ((bool) $input->getOption('json')) { $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)); return self::SUCCESS; }
        $output->writeln('<info>SPECIALIZED_AUTHORSHIP_COMMISSIONS_ISSUED</info> '.$result['case_id']);
        foreach ($result['commissions'] as $commission) $output->writeln('- '.$commission['office'].' '.$commission['commission_id'].' '.$commission['status']);
        $output->writeln('Authorship authority: PRESENT; PENDING RECIPIENT ACCEPTANCE');
        $output->writeln('Persona assembly authority: NOT GRANTED'); $output->writeln('Spawning authority: NOT GRANTED');
        $output->writeln('Admission authority: NOT GRANTED'); $output->writeln('Execution authority: NOT GRANTED');
        return self::SUCCESS;
    }
}
