<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Senate\SubordinatePersonaPressureTrialService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: "imperium:senate:conduct-subordinate-persona-pressure-trials",
    description: "Seal Governance and Security pressure trials",
)]
final class SenateConductSubordinatePersonaPressureTrialsCommand extends Command
{
    public function __construct(private readonly SubordinatePersonaPressureTrialService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument("fresh-consistency-trial-id", InputArgument::REQUIRED)
            ->addOption("json", null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $record = $this->service->conduct(
                (string) $input->getArgument("fresh-consistency-trial-id"),
            );
        } catch (\Throwable $exception) {
            $output->writeln("<error>REFUSED</error> " . $exception->getMessage());
            return self::FAILURE;
        }
        $output->writeln($input->getOption("json")
            ? json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            : "<info>SUBORDINATE_PERSONA_REQUIRED_TRIALS_SEALED</info> " . $record["ledger_id"]);
        return self::SUCCESS;
    }
}
