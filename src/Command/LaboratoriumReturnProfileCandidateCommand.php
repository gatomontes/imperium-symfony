<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Laboratorium\ProfileCandidateReturnService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:laboratorium:return-profile-candidate', description: 'Return one exact sealed Profile candidate to Conscription')]
final class LaboratoriumReturnProfileCandidateCommand extends Command
{
    public function __construct(private readonly ProfileCandidateReturnService $service) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('candidate-id', InputArgument::REQUIRED)->addOption('json', null, InputOption::VALUE_NONE); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try { $return = $this->service->returnCandidate((string) $input->getArgument('candidate-id')); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        if ((bool) $input->getOption('json')) { $output->writeln(json_encode($return, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)); return self::SUCCESS; }
        $output->writeln('<info>PROFILE_CANDIDATE_RETURNED_TO_CONSCRIPTION</info> '.$return['return_id']);
        $output->writeln('Status: '.$return['status']);
        $output->writeln('Recipient acceptance: PENDING');
        $output->writeln('Garrison custody: RETAINED');
        return self::SUCCESS;
    }
}
