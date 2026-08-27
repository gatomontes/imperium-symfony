<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Senate\SubordinatePersonaSenateDispositionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: "imperium:senate:issue-subordinate-persona-disposition",
    description: "Seal the Lord Speaker's exact Persona disposition",
)]
final class SenateIssueSubordinatePersonaDispositionCommand extends Command
{
    public function __construct(
        private readonly SubordinatePersonaSenateDispositionService $service,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument("disposition-opening-id", InputArgument::REQUIRED)
            ->addOption("json", null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $record = $this->service->issue(
                (string) $input->getArgument("disposition-opening-id"),
            );
        } catch (\Throwable $exception) {
            $output->writeln("<error>REFUSED</error> " . $exception->getMessage());
            return self::FAILURE;
        }
        $output->writeln($input->getOption("json")
            ? json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            : "<info>SUBORDINATE_PERSONA_SENATE_DISPOSITION_SEALED</info> " . $record["disposition_id"]);
        return self::SUCCESS;
    }
}
