<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Bootstrap\OperatorRootOperationalizationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[
    AsCommand(
        name: "imperium:operator:declare-operational",
        description: "Permanently close operator-root installation without forcing the optional upgrade program",
    ),
]
final class OperatorDeclareImperiumOperationalCommand extends Command
{
    public function __construct(
        private readonly OperatorRootOperationalizationService $service,
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
            $result = $this->service->seal(
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
            "<info>IMPERIUM_OPERATIONAL</info> " . $result["seal_id"],
        );
        $output->writeln(
            "Operator-root installation window: PERMANENTLY CLOSED",
        );
        $output->writeln("Upgrade program: DEFERRED FOR TEST-DRIVE");
        return self::SUCCESS;
    }
}
