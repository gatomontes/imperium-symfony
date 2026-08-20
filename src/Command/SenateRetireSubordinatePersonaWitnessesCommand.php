<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Senate\SubordinatePersonaWitnessRetirementService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: "imperium:senate:retire-subordinate-persona-witnesses",
    description: "Retire every sterile witness after Senate disposition",
)]
final class SenateRetireSubordinatePersonaWitnessesCommand extends Command
{
    public function __construct(
        private readonly SubordinatePersonaWitnessRetirementService $service,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument("disposition-id", InputArgument::REQUIRED)
            ->addOption("json", null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $record = $this->service->retire(
                (string) $input->getArgument("disposition-id"),
            );
        } catch (\Throwable $exception) {
            $output->writeln("<error>REFUSED</error> " . $exception->getMessage());
            return self::FAILURE;
        }
        $output->writeln($input->getOption("json")
            ? json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            : "<info>SUBORDINATE_PERSONA_WITNESSES_RETIRED</info> " . $record["retirement_set_id"]);
        return self::SUCCESS;
    }
}
