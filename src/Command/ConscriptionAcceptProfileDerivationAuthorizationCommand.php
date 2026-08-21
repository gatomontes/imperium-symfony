<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Conscription\ProfileDerivationAuthorizationAcceptanceService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:conscription:accept-profile-derivation', description: 'Accept one exact authorized Profile-derivation route and request its Garrison handoff')]
final class ConscriptionAcceptProfileDerivationAuthorizationCommand extends Command
{
    public function __construct(private readonly ProfileDerivationAuthorizationAcceptanceService $service) { parent::__construct(); }
    protected function configure(): void
    {
        $this->addArgument('act-id', InputArgument::REQUIRED)->addOption('json', null, InputOption::VALUE_NONE);
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try { $result = $this->service->accept((string) $input->getArgument('act-id')); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        if ((bool) $input->getOption('json')) { $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)); return self::SUCCESS; }
        $output->writeln('<info>PROFILE_DERIVATION_ACCEPTED</info> '.$result['acceptance']['acceptance_id']);
        $output->writeln('Garrison request: '.$result['handoff_request']['request_id']);
        $output->writeln('Status: '.$result['handoff_request']['status']);
        $output->writeln('Custody movement: NOT AUTHORIZED');
        $output->writeln('Laboratorium commission: NOT AUTHORIZED');
        return self::SUCCESS;
    }
}
