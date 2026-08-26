<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Senate\SubordinatePersonaFirstTestimonyService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: "imperium:senate:author-subordinate-persona-first-question",
    description: "Assign Practice and seal its exact first question pending separately authorized testimony",
)]
final class SenateConductSubordinatePersonaFirstTestimonyCommand extends Command
{
    public function __construct(private readonly SubordinatePersonaFirstTestimonyService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument("deposition-id", InputArgument::REQUIRED)
            ->addOption("json", null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $record = $this->service->conduct((string) $input->getArgument("deposition-id"));
        } catch (\Throwable $exception) {
            $output->writeln("<error>REFUSED</error> " . $exception->getMessage());
            return self::FAILURE;
        }
        $output->writeln($input->getOption("json")
            ? json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            : "<info>SUBORDINATE_PERSONA_FIRST_QUESTION_SEALED_PENDING_TESTIMONY_AUTHORIZATION</info> " . $record["question_record_id"]);
        return self::SUCCESS;
    }
}
