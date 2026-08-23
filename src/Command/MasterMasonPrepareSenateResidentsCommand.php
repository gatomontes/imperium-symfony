<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Senate\SenateResidentProvisioningCaseService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[
    AsCommand(
        name: "imperium:mastermason:prepare-senate-residents",
        description: "Open separate non-authorizing Lord Speaker and Bailiff provisioning cases",
    ),
]
final class MasterMasonPrepareSenateResidentsCommand extends Command
{
    public function __construct(
        private readonly SenateResidentProvisioningCaseService $service,
    ) {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->addArgument(
            "activation-demand-id",
            InputArgument::REQUIRED,
        )->addOption("json", null, InputOption::VALUE_NONE);
    }
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        try {
            $result = $this->service->open(
                (string) $input->getArgument("activation-demand-id"),
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
            "<info>SENATE_RESIDENT_PROVISIONING_CASES_OPENED</info> " .
                count($result["cases"]),
        );
        foreach ($result["cases"] as $case) {
            $output->writeln($case["target_seat"] . ": " . $case["status"]);
        }
        $output->writeln(
            "Construction, spawning, Seat binding, proceeding, and execution authority: NOT GRANTED",
        );
        return self::SUCCESS;
    }
}
