<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Senate\SubordinatePersonaFirstTestimonyCompletionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:senate:complete-subordinate-persona-first-testimony', description: 'Consume the exact authorized sealed Practice question and seal sterile Persona testimony')]
final class SenateCompleteSubordinatePersonaFirstTestimonyCommand extends Command
{
    public function __construct(private readonly SubordinatePersonaFirstTestimonyCompletionService $service) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('question-record-id', InputArgument::REQUIRED)->addOption('json', null, InputOption::VALUE_NONE); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try { $record = $this->service->complete((string) $input->getArgument('question-record-id')); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        $output->writeln($input->getOption('json') ? json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) : '<info>SUBORDINATE_PERSONA_FIRST_TESTIMONY_SEALED</info> '.$record['turn_id']);
        return self::SUCCESS;
    }
}
