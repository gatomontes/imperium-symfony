<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Bootstrap\OperatorRootPersonnelInstallationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[
    AsCommand(
        name: "imperium:operator:install-founding-personnel",
        description: "Mechanically install operator-supplied founding Personas, Profiles, and Officers",
    ),
]
final class OperatorInstallFoundingPersonnelCommand extends Command
{
    public function __construct(
        private readonly OperatorRootPersonnelInstallationService $service,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument("package", InputArgument::REQUIRED)->addOption(
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
            $path = (string) $input->getArgument("package");
            if (!is_file($path)) {
                throw new \RuntimeException("Operator package is absent.");
            }
            $package = json_decode(
                (string) file_get_contents($path),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
            $result = $this->service->install($package);
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
        $output->writeln("<info>FOUNDING_PERSONNEL_INSTALLED</info>");
        foreach ($result["installations"] as $installation) {
            $output->writeln($installation["seat"] . ": ACTIVE");
        }
        $output->writeln("Provenance: OPERATOR_ROOT_INSTALLATION");
        $output->writeln(
            "Internal authorization, construction, and admission: NOT REQUIRED",
        );
        return self::SUCCESS;
    }
}
