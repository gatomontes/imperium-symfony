<?php
declare(strict_types=1);
namespace App\Command;
use App\Imperium\Runtime\Garrison\AdversarialReviewerAvailabilityResponseService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
#[
    AsCommand(
        name: "imperium:garrison:answer-adversarial-reviewer-availability",
        description: "Return exact admitted Reviewer custody facts to Curia",
    ),
]
final class GarrisonAnswerAdversarialReviewerAvailabilityCommand extends Command
{
    public function __construct(
        private readonly AdversarialReviewerAvailabilityResponseService $service,
    ) {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->addArgument("inquiry-id", InputArgument::REQUIRED)->addOption(
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
            $response = $this->service->respond(
                (string) $input->getArgument("inquiry-id"),
            );
        } catch (\Throwable $e) {
            $output->writeln("<error>REFUSED</error> " . $e->getMessage());
            return self::FAILURE;
        }
        if ($input->getOption("json")) {
            $output->writeln(
                json_encode(
                    $response,
                    JSON_PRETTY_PRINT |
                        JSON_UNESCAPED_SLASHES |
                        JSON_THROW_ON_ERROR,
                ),
            );
            return self::SUCCESS;
        }
        $output->writeln(
            "<info>ADVERSARIAL_REVIEWER_AVAILABILITY_ANSWERED</info> " .
                $response["response_id"],
        );
        $output->writeln("Finding: " . $response["ledger_finding"]);
        $output->writeln(
            "Matches: " . $response["matching_identity_record_count"],
        );
        $output->writeln(
            "Suitability, selection, review, and exception authority: NOT GRANTED",
        );
        return self::SUCCESS;
    }
}
