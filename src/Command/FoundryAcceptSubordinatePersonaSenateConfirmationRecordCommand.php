<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Foundry\SubordinatePersonaSenateConfirmationRecordAcceptanceService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: "imperium:foundry:accept-subordinate-persona-senate-confirmation-record", description: "Accept and route the exact Senate confirmation record")]
final class FoundryAcceptSubordinatePersonaSenateConfirmationRecordCommand extends Command
{
    public function __construct(private readonly SubordinatePersonaSenateConfirmationRecordAcceptanceService $service) { parent::__construct(); }

    protected function configure(): void
    {
        $this->addArgument("confirmation-record-id", InputArgument::REQUIRED)
            ->addArgument("artificer-binding-id", InputArgument::REQUIRED)
            ->addOption("json", null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $record = $this->service->accept((string) $input->getArgument("confirmation-record-id"), (string) $input->getArgument("artificer-binding-id"));
        } catch (\Throwable $exception) {
            $output->writeln("<error>REFUSED</error> " . $exception->getMessage());
            return self::FAILURE;
        }
        $output->writeln($input->getOption("json") ? json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) : "<info>SUBORDINATE_PERSONA_SENATE_CONFIRMATION_RECORD_ACCEPTED</info> " . $record["acceptance_id"]);
        return self::SUCCESS;
    }
}
