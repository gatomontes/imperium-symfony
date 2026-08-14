<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Senate\PersonaConfirmationCaseIntakeService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[
    AsCommand(
        name: "imperium:senate:preserve-confirmation-case",
        description: "Preserve an exact Persona confirmation request without impersonating vacant Senate Seats",
    ),
]
final class SenatePreservePersonaConfirmationCaseCommand extends Command
{
    public function __construct(
        private readonly PersonaConfirmationCaseIntakeService $service,
    ) {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->addArgument(
            "confirmation-request-id",
            InputArgument::REQUIRED,
        )->addOption("json", null, InputOption::VALUE_NONE);
    }
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        try {
            $case = $this->service->preserve(
                (string) $input->getArgument("confirmation-request-id"),
            );
        } catch (\Throwable $e) {
            $output->writeln("<error>REFUSED</error> " . $e->getMessage());
            return self::FAILURE;
        }
        if ($input->getOption("json")) {
            $output->writeln(
                json_encode(
                    $case,
                    JSON_PRETTY_PRINT |
                        JSON_UNESCAPED_SLASHES |
                        JSON_THROW_ON_ERROR,
                ),
            );
            return self::SUCCESS;
        }
        $output->writeln(
            "<info>SENATE_CONFIRMATION_CASE_PRESERVED</info> " .
                $case["confirmation_case_id"],
        );
        $output->writeln("Status: " . $case["status"]);
        foreach ($case["activation_required"] as $seat) {
            $output->writeln("Activation required: " . $seat);
        }
        $output->writeln(
            "Assembly, witness, finding, admission, and execution authority: NOT GRANTED",
        );
        return self::SUCCESS;
    }
}
