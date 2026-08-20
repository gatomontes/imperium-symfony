<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Senate\SubordinatePersonaSenatorFindingService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: "imperium:senate:issue-subordinate-persona-senator-findings",
    description: "Seal four independent attributable Senator findings",
)]
final class SenateIssueSubordinatePersonaSenatorFindingsCommand extends Command
{
    public function __construct(private readonly SubordinatePersonaSenatorFindingService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument("required-trial-ledger-id", InputArgument::REQUIRED)
            ->addOption("json", null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $record = $this->service->issue(
                (string) $input->getArgument("required-trial-ledger-id"),
            );
        } catch (\Throwable $exception) {
            $output->writeln("<error>REFUSED</error> " . $exception->getMessage());
            return self::FAILURE;
        }
        $output->writeln($input->getOption("json")
            ? json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            : "<info>SUBORDINATE_PERSONA_SENATOR_FINDINGS_SEALED</info> " . $record["finding_set_id"]);
        return self::SUCCESS;
    }
}
