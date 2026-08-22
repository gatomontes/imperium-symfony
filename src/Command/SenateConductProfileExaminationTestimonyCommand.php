<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Senate\ProfileExaminationTestimonyService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:senate:conduct-profile-examination-testimony', description: 'Dispatch one sealed Profile-examination question unchanged and seal its answer')]
final class SenateConductProfileExaminationTestimonyCommand extends Command
{
    public function __construct(private readonly ProfileExaminationTestimonyService $service) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('question-id', InputArgument::REQUIRED)->addOption('json', null, InputOption::VALUE_NONE); }
    protected function execute(InputInterface $input, OutputInterface $output): int { try { $result = $this->service->conduct((string) $input->getArgument('question-id')); } catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; } $output->writeln($input->getOption('json') ? json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) : '<info>PROFILE_EXAMINATION_TESTIMONY_ANSWER_SEALED</info> '.$result['turn']['turn_id']); return self::SUCCESS; }
}
