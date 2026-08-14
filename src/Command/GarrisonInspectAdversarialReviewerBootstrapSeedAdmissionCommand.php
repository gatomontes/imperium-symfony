<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Garrison\AdversarialReviewerBootstrapSeedAdmissionIntakeService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[
    AsCommand(
        name: "imperium:garrison:inspect-adversarial-reviewer-bootstrap-seed",
        description: "Inspect the exact initial Reviewer Persona admission package and return bounded defects",
    ),
]
final class GarrisonInspectAdversarialReviewerBootstrapSeedAdmissionCommand
    extends Command
{
    public function __construct(
        private readonly AdversarialReviewerBootstrapSeedAdmissionIntakeService $service,
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
            $return = $this->service->inspect(
                (string) $input->getArgument("delivery-id"),
            );
        } catch (\Throwable $e) {
            $output->writeln("<error>REFUSED</error> " . $e->getMessage());
            return self::FAILURE;
        }
        if ($input->getOption("json")) {
            $output->writeln(
                json_encode(
                    $return,
                    JSON_PRETTY_PRINT |
                        JSON_UNESCAPED_SLASHES |
                        JSON_THROW_ON_ERROR,
                ),
            );
            return self::SUCCESS;
        }
        $output->writeln(
            "<comment>ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_ADMISSION_RETURNED</comment> " .
                $return["return_id"],
        );
        $output->writeln("Disposition: " . $return["disposition"]);
        foreach ($return["defects"] as $defect) {
            $output->writeln("Defect: " . $defect);
        }
        $output->writeln("Custody created: NO");
        return self::SUCCESS;
    }
}
