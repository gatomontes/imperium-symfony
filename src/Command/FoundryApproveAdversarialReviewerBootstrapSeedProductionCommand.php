<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Foundry\AdversarialReviewerBootstrapSeedProductionApprovalService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[
    AsCommand(
        name: "imperium:foundry:approve-adversarial-reviewer-bootstrap-seed",
        description: "Approve the exact initial Adversarial Reviewer bootstrap seed as Foundry production",
    ),
]
final class FoundryApproveAdversarialReviewerBootstrapSeedProductionCommand
    extends Command
{
    public function __construct(
        private readonly AdversarialReviewerBootstrapSeedProductionApprovalService $service,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument("acceptance-id", InputArgument::REQUIRED)->addOption(
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
            $approval = $this->service->approve(
                (string) $input->getArgument("acceptance-id"),
            );
        } catch (\Throwable $e) {
            $output->writeln("<error>REFUSED</error> " . $e->getMessage());
            return self::FAILURE;
        }
        if ($input->getOption("json")) {
            $output->writeln(
                json_encode(
                    $approval,
                    JSON_PRETTY_PRINT |
                        JSON_UNESCAPED_SLASHES |
                        JSON_THROW_ON_ERROR,
                ),
            );
            return self::SUCCESS;
        }
        $output->writeln(
            "<info>ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_PRODUCTION_APPROVED</info> " .
                $approval["production_approval_id"],
        );
        $output->writeln(
            "Candidate: " .
                $approval["persona_candidate_id"] .
                "@" .
                $approval["persona_version"],
        );
        $output->writeln("Status: APPROVED PENDING GARRISON ADMISSION");
        $output->writeln(
            "Admission, spawning, Seat binding, review findings, and execution: NOT GRANTED",
        );
        return self::SUCCESS;
    }
}
