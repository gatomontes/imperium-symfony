<?php
declare(strict_types=1);
namespace App\Command;
use App\Imperium\Runtime\Garrison\ExternalReviewerAdmissionDispositionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
#[
    AsCommand(
        name: "imperium:garrison:evaluate-external-reviewer-admission",
        description: "Have the occupied Constable evaluate an external Reviewer Persona intake for admission",
    ),
]
final class GarrisonEvaluateExternalReviewerAdmissionCommand extends Command
{
    public function __construct(
        private readonly ExternalReviewerAdmissionDispositionService $service,
    ) {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->addArgument("intake-id", InputArgument::REQUIRED)->addOption(
            "json",
            null,
            InputOption::VALUE_NONE,
        );
    }
    protected function execute(InputInterface $i, OutputInterface $o): int
    {
        try {
            $r = $this->service->evaluate(
                (string) $i->getArgument("intake-id"),
            );
        } catch (\Throwable $e) {
            $o->writeln("<error>REFUSED</error> " . $e->getMessage());
            return self::FAILURE;
        }
        if ($i->getOption("json")) {
            $o->writeln(
                json_encode(
                    $r,
                    JSON_PRETTY_PRINT |
                        JSON_UNESCAPED_SLASHES |
                        JSON_THROW_ON_ERROR,
                ),
            );
            return self::SUCCESS;
        }
        $o->writeln(
            "<info>EXTERNAL_REVIEWER_ADMISSION_DISPOSITION_SEALED</info> " .
                $r["disposition_id"],
        );
        $o->writeln("Disposition: " . $r["disposition"]);
        foreach ($r["evaluation"]["missing_evidence"] as $e) {
            $o->writeln("- " . $e);
        }
        $o->writeln("Custody and review eligibility: NOT GRANTED");
        return self::SUCCESS;
    }
}
