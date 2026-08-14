<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Senate\SenateActivationDemandService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[
    AsCommand(
        name: "imperium:senate:demand-resident-activation",
        description: "Demand construction and activation of vacant resident Senate Seats for an exact preserved case",
    ),
]
final class SenateDemandResidentActivationCommand extends Command
{
    public function __construct(
        private readonly SenateActivationDemandService $service,
    ) {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->addArgument(
            "confirmation-case-id",
            InputArgument::REQUIRED,
        )->addOption("json", null, InputOption::VALUE_NONE);
    }
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        try {
            $demand = $this->service->demand(
                (string) $input->getArgument("confirmation-case-id"),
            );
        } catch (\Throwable $e) {
            $output->writeln("<error>REFUSED</error> " . $e->getMessage());
            return self::FAILURE;
        }
        if ($input->getOption("json")) {
            $output->writeln(
                json_encode(
                    $demand,
                    JSON_PRETTY_PRINT |
                        JSON_UNESCAPED_SLASHES |
                        JSON_THROW_ON_ERROR,
                ),
            );
            return self::SUCCESS;
        }
        $output->writeln(
            "<info>SENATE_RESIDENT_ACTIVATION_REQUIRED</info> " .
                $demand["demand_id"],
        );
        foreach ($demand["required_seats"] as $seat) {
            $output->writeln("Construction required: " . $seat["seat"]);
        }
        $output->writeln(
            "Construction, spawning, Seat binding, proceeding, and execution authority: NOT GRANTED",
        );
        return self::SUCCESS;
    }
}
