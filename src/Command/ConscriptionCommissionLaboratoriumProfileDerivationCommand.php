<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Conscription\LaboratoriumProfileDerivationCommissionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:conscription:commission-profile-derivation', description: 'Commission Laboratorium for one exact custody-bound Profile derivation')]
final class ConscriptionCommissionLaboratoriumProfileDerivationCommand extends Command
{
    public function __construct(private readonly LaboratoriumProfileDerivationCommissionService $service) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('handoff-disposition-id', InputArgument::REQUIRED)->addOption('json', null, InputOption::VALUE_NONE); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try { $commission = $this->service->commission((string) $input->getArgument('handoff-disposition-id')); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        if ((bool) $input->getOption('json')) { $output->writeln(json_encode($commission, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)); return self::SUCCESS; }
        $output->writeln('<info>LABORATORIUM_PROFILE_DERIVATION_COMMISSIONED</info> '.$commission['commission_id']);
        $output->writeln('Status: '.$commission['status']);
        $output->writeln('Garrison custody: RETAINED');
        $output->writeln('Profile artifact: NOT YET CREATED');
        return self::SUCCESS;
    }
}
