<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Senate\ExaminationAssemblyAuthorizationDispositionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:senate:decide-examination-assembly-authorization', description: 'Accept or refuse one exact examination-assembly request')]
final class SenateDecideExaminationAssemblyAuthorizationCommand extends Command
{
    public function __construct(private readonly ExaminationAssemblyAuthorizationDispositionService $service) { parent::__construct(); }
    protected function configure(): void
    {
        $this->addArgument('request-id', InputArgument::REQUIRED)->addArgument('binding-id', InputArgument::REQUIRED)
            ->addArgument('disposition', InputArgument::REQUIRED, 'ACCEPTED or REFUSED')->addArgument('rationale', InputArgument::REQUIRED)->addOption('json', null, InputOption::VALUE_NONE);
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try { $record = $this->service->decide((string) $input->getArgument('request-id'), (string) $input->getArgument('binding-id'), (string) $input->getArgument('disposition'), (string) $input->getArgument('rationale')); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        if ((bool) $input->getOption('json')) { $output->writeln(json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)); return self::SUCCESS; }
        $output->writeln('<info>EXAMINATION_ASSEMBLY_INTAKE_DECIDED</info> '.$record['disposition_id']);
        $output->writeln('Disposition: '.$record['disposition']);
        $output->writeln('Status: '.$record['status']);
        $output->writeln('Assembly: NOT PERFORMED');
        return self::SUCCESS;
    }
}
