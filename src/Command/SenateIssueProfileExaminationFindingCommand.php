<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Senate\ProfileExaminationSenatorFindingService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:senate:issue-profile-examination-finding', description: 'Author and seal one exact jurisdiction-bound Profile examination finding')]
final class SenateIssueProfileExaminationFindingCommand extends Command
{
    public function __construct(private readonly ProfileExaminationSenatorFindingService $service) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('opening-id', InputArgument::REQUIRED)->addArgument('jurisdiction', InputArgument::REQUIRED)->addArgument('senator-binding-id', InputArgument::REQUIRED)->addOption('json', null, InputOption::VALUE_NONE); }
    protected function execute(InputInterface $input, OutputInterface $output): int { try { $result = $this->service->issue((string) $input->getArgument('opening-id'), (string) $input->getArgument('jurisdiction'), (string) $input->getArgument('senator-binding-id')); } catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; } $output->writeln($input->getOption('json') ? json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) : '<info>PROFILE_EXAMINATION_SENATOR_FINDING_SEALED</info> '.$result['finding']['finding_id']); return self::SUCCESS; }
}
