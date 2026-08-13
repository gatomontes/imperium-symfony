<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Foundry\SubordinatePersonaReviewService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[
    AsCommand(
        name: "imperium:foundry:review-subordinate-persona",
        description: "Review one assembled subordinate Persona candidate before adversarial examination",
    ),
]
final class FoundryReviewSubordinatePersonaCommand extends Command
{
    public function __construct(
        private readonly SubordinatePersonaReviewService $service,
    ) {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->addArgument("candidate-id", InputArgument::REQUIRED)->addOption(
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
            $review = $this->service->review(
                (string) $input->getArgument("candidate-id"),
            );
        } catch (\Throwable $exception) {
            $output->writeln(
                "<error>REFUSED</error> " . $exception->getMessage(),
            );
            return self::FAILURE;
        }
        if ($input->getOption("json")) {
            $output->writeln(
                json_encode(
                    $review,
                    JSON_PRETTY_PRINT |
                        JSON_UNESCAPED_SLASHES |
                        JSON_THROW_ON_ERROR,
                ),
            );
            return self::SUCCESS;
        }
        $output->writeln(
            "<info>SUBORDINATE_PERSONA_REVIEW_SEALED</info> " .
                $review["review_id"],
        );
        $output->writeln("Candidate: " . $review["candidate_id"]);
        $output->writeln("Status: " . $review["status"]);
        $output->writeln("Persona approval authority: NOT GRANTED");
        $output->writeln("Admission authority: NOT GRANTED");
        return self::SUCCESS;
    }
}
