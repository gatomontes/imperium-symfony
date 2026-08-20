<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Senate\SubordinatePersonaJurisdictionBaselineService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: "imperium:senate:complete-subordinate-persona-jurisdiction-baseline",
    description: "Seal Governance, Consistency, and Security baseline testimony",
)]
final class SenateCompleteSubordinatePersonaJurisdictionBaselineCommand extends Command
{
    public function __construct(private readonly SubordinatePersonaJurisdictionBaselineService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument("first-turn-id", InputArgument::REQUIRED)
            ->addOption("json", null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $record = $this->service->complete((string) $input->getArgument("first-turn-id"));
        } catch (\Throwable $exception) {
            $output->writeln("<error>REFUSED</error> " . $exception->getMessage());
            return self::FAILURE;
        }
        $output->writeln($input->getOption("json")
            ? json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            : "<info>SUBORDINATE_PERSONA_JURISDICTION_BASELINE_COMPLETE</info> " . $record["baseline_id"]);
        return self::SUCCESS;
    }
}
