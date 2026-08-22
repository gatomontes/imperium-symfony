<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Senate\ProfileExaminationFindingAuthorityOpeningService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:senate:open-profile-examination-finding-authority', description: 'Open bounded Senator finding authorities for one complete Profile testimony baseline')]
final class SenateOpenProfileExaminationFindingAuthorityCommand extends Command
{
    public function __construct(private readonly ProfileExaminationFindingAuthorityOpeningService $service) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('readiness-id', InputArgument::REQUIRED)->addArgument('lord-speaker-binding-id', InputArgument::REQUIRED)->addOption('json', null, InputOption::VALUE_NONE); }
    protected function execute(InputInterface $input, OutputInterface $output): int { try { $opening = $this->service->open((string) $input->getArgument('readiness-id'), (string) $input->getArgument('lord-speaker-binding-id')); } catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; } $output->writeln($input->getOption('json') ? json_encode($opening, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) : '<info>PROFILE_EXAMINATION_FINDING_AUTHORITIES_OPENED</info> '.$opening['opening_id']); return self::SUCCESS; }
}
