<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Laboratorium\ProfileDerivationCommissionAcceptanceService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:laboratorium:accept-profile-derivation', description: 'Accept one exact custody-bound Profile-derivation commission')]
final class LaboratoriumAcceptProfileDerivationCommissionCommand extends Command
{
    public function __construct(private readonly ProfileDerivationCommissionAcceptanceService $service) { parent::__construct(); }
    protected function configure(): void
    {
        $this->addArgument('commission-id', InputArgument::REQUIRED)->addArgument('alchemist-binding-id', InputArgument::REQUIRED)->addOption('json', null, InputOption::VALUE_NONE);
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try { $acceptance = $this->service->accept((string) $input->getArgument('commission-id'), (string) $input->getArgument('alchemist-binding-id')); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        if ((bool) $input->getOption('json')) { $output->writeln(json_encode($acceptance, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)); return self::SUCCESS; }
        $output->writeln('<info>PROFILE_DERIVATION_COMMISSION_ACCEPTED</info> '.$acceptance['acceptance_id']);
        $output->writeln('Status: '.$acceptance['status']);
        $output->writeln('Garrison custody: RETAINED');
        $output->writeln('Profile artifact: NOT YET CREATED');
        return self::SUCCESS;
    }
}
