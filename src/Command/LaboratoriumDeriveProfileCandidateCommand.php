<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Laboratorium\ProfileCandidateDerivationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:laboratorium:derive-profile-candidate', description: 'Derive, version, and seal one exact custody-bound Profile candidate')]
final class LaboratoriumDeriveProfileCandidateCommand extends Command
{
    public function __construct(private readonly ProfileCandidateDerivationService $service) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('acceptance-id', InputArgument::REQUIRED)->addOption('json', null, InputOption::VALUE_NONE); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try { $candidate = $this->service->derive((string) $input->getArgument('acceptance-id')); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        if ((bool) $input->getOption('json')) { $output->writeln(json_encode($candidate, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)); return self::SUCCESS; }
        $output->writeln('<info>PROFILE_CANDIDATE_DERIVED_AND_SEALED</info> '.$candidate['candidate_id']);
        $output->writeln('Profile: '.$candidate['profile_id'].' v'.$candidate['profile_version']);
        $output->writeln('Status: '.$candidate['status']);
        $output->writeln('Garrison custody: RETAINED');
        $output->writeln('Return to Conscription: NOT YET PERFORMED');
        return self::SUCCESS;
    }
}
