<?php

declare(strict_types=1);

namespace App\Command;

use App\Bootstrap\MasterMason;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[
    AsCommand(
        name: "imperium:activate",
        description: "Install required generic v0 occupants and bring Imperium to CURIA_READY",
    ),
]
final class ActivateCommand extends Command
{
    public function __construct(
        private readonly MasterMason $masterMason,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            "instance-id",
            InputArgument::OPTIONAL,
            "Immutable instance identifier",
            "imperium-local",
        )->addOption(
            "prepare-upgrades",
            null,
            InputOption::VALUE_NONE,
            "Prepare but do not start the ordered v0 upgrade plan",
        );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        try {
            $result = $this->masterMason->activate(
                (string) $input->getArgument("instance-id"),
                (bool) $input->getOption("prepare-upgrades"),
            );
        } catch (\Throwable $exception) {
            $output->writeln(
                "<error>REFUSED</error> " . $exception->getMessage(),
            );
            return self::FAILURE;
        }
        $record = $result["state"];
        $output->writeln(
            "<info>" .
                $record["state"] .
                "</info> generation " .
                $record["generation"],
        );
        $output->writeln("Runtime: MASTERMASON");
        $output->writeln("Activation: OPERATOR_ROOT_V0");
        $output->writeln(
            "Upgrade program: " . $result["upgrade_program_status"],
        );
        return self::SUCCESS;
    }
}
