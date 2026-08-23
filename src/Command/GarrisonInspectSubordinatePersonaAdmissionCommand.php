<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Garrison\SubordinatePersonaAdmissionIntakeService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[
    AsCommand(
        name: "imperium:garrison:inspect-subordinate-persona-admission",
        description: "Inspect an exact subordinate Persona admission package",
    ),
]
final class GarrisonInspectSubordinatePersonaAdmissionCommand extends Command
{
    public function __construct(
        private readonly SubordinatePersonaAdmissionIntakeService $service,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument("delivery-id", InputArgument::REQUIRED)->addOption(
            "json",
            null,
            InputOption::VALUE_NONE,
        );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        try {
            $record = $this->service->inspect(
                (string) $input->getArgument("delivery-id"),
            );
        } catch (\Throwable $exception) {
            $output->writeln(
                "<error>REFUSED</error> " . $exception->getMessage(),
            );
            return self::FAILURE;
        }
        $output->writeln(
            $input->getOption("json")
                ? json_encode(
                    $record,
                    JSON_PRETTY_PRINT |
                        JSON_UNESCAPED_SLASHES |
                        JSON_THROW_ON_ERROR,
                )
                : "<info>" .
                    $record["disposition"] .
                    "</info> " .
                    $record["return_id"],
        );
        return self::SUCCESS;
    }
}
