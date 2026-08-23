<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Senate\ProfileExaminationQuestionAuthorshipService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:senate:author-profile-examination-question', description: 'Author and seal one accepted Senator jurisdiction question without dispatch')]
final class SenateAuthorProfileExaminationQuestionCommand extends Command
{
    public function __construct(private readonly ProfileExaminationQuestionAuthorshipService $service) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('opening-id', InputArgument::REQUIRED)->addArgument('acceptance-id', InputArgument::REQUIRED)->addArgument('senator-binding-id', InputArgument::REQUIRED)->addOption('json', null, InputOption::VALUE_NONE); }
    protected function execute(InputInterface $input, OutputInterface $output): int { try { $question = $this->service->author((string) $input->getArgument('opening-id'), (string) $input->getArgument('acceptance-id'), (string) $input->getArgument('senator-binding-id')); } catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; } $output->writeln($input->getOption('json') ? json_encode($question, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) : '<info>PROFILE_EXAMINATION_QUESTION_AUTHORED_SEALED</info> '.$question['question_id']); return self::SUCCESS; }
}
