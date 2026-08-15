<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Bootstrap\RequiredV0PersonnelInstallationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[
    AsCommand(
        name: "imperium:operator:install-required-v0",
        description: "Occupy every required founding Seat with a generic artifact-free v0 placeholder",
    ),
]
final class OperatorInstallRequiredV0PersonnelCommand extends Command
{
    public function __construct(
        private readonly RequiredV0PersonnelInstallationService $service,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument("instance-id", InputArgument::REQUIRED)->addOption(
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
            $result = $this->service->install(
                (string) $input->getArgument("instance-id"),
            );
        } catch (\Throwable $e) {
            $output->writeln("<error>REFUSED</error> " . $e->getMessage());
            return self::FAILURE;
        }
        if ($input->getOption("json")) {
            $output->writeln(
                json_encode(
                    $result,
                    JSON_PRETTY_PRINT |
                        JSON_UNESCAPED_SLASHES |
                        JSON_THROW_ON_ERROR,
                ),
            );
            return self::SUCCESS;
        }
        $output->writeln(
            "<info>ALL_REQUIRED_V0_SEATS_OCCUPIED_PRE_OPERATIONAL</info>",
        );
        $output->writeln("Seats: " . $result["required_seat_count"]);
        $output->writeln(
            "Placeholder: GENERIC v0; Persona/Profile/Officer artifacts: NONE",
        );
        $output->writeln(
            "First operational order: GOVERNED REPLACEMENT OR UPGRADE OF EVERY v0 OCCUPANT",
        );
        return self::SUCCESS;
    }
}
