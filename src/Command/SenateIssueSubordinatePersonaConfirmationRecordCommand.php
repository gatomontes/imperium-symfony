<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Senate\SubordinatePersonaConfirmationRecordIssuanceService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: "imperium:senate:issue-subordinate-persona-confirmation-record",
    description: "Issue the immutable Senate confirmation record to Foundry",
)]
final class SenateIssueSubordinatePersonaConfirmationRecordCommand extends Command
{
    public function __construct(
        private readonly SubordinatePersonaConfirmationRecordIssuanceService $service,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument("retirement-set-id", InputArgument::REQUIRED)
            ->addOption("json", null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $record = $this->service->issue(
                (string) $input->getArgument("retirement-set-id"),
            );
        } catch (\Throwable $exception) {
            $output->writeln("<error>REFUSED</error> " . $exception->getMessage());
            return self::FAILURE;
        }
        $output->writeln($input->getOption("json")
            ? json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            : "<info>SUBORDINATE_PERSONA_CONFIRMATION_RECORD_ISSUED</info> " . $record["confirmation_record_id"]);
        return self::SUCCESS;
    }
}
