<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Garrison\ProfileDerivationHandoffDispositionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:garrison:decide-profile-derivation-handoff', description: 'Approve or refuse one exact custody-bound Profile-derivation handoff')]
final class GarrisonDecideProfileDerivationHandoffCommand extends Command
{
    public function __construct(private readonly ProfileDerivationHandoffDispositionService $service) { parent::__construct(); }
    protected function configure(): void
    {
        $this->addArgument('request-id', InputArgument::REQUIRED)
            ->addArgument('constable-binding-id', InputArgument::REQUIRED)
            ->addArgument('disposition', InputArgument::REQUIRED, 'APPROVED or REFUSED')
            ->addArgument('rationale', InputArgument::REQUIRED)
            ->addOption('json', null, InputOption::VALUE_NONE);
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try { $record = $this->service->decide((string) $input->getArgument('request-id'), (string) $input->getArgument('constable-binding-id'), (string) $input->getArgument('disposition'), (string) $input->getArgument('rationale')); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        if ((bool) $input->getOption('json')) { $output->writeln(json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)); return self::SUCCESS; }
        $output->writeln('<info>PROFILE_DERIVATION_HANDOFF_DISPOSITION_RECORDED</info> '.$record['disposition_id']);
        $output->writeln('Disposition: '.$record['disposition']);
        $output->writeln('Status: '.$record['status']);
        $output->writeln('Garrison custody: RETAINED');
        $output->writeln('Laboratorium commission: NOT YET ISSUED');
        return self::SUCCESS;
    }
}
