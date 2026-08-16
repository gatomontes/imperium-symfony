<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Foundry\AdversarialReviewAcceptanceService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[
    AsCommand(
        name: "imperium:foundry:accept-adversarial-review",
        description: "Have the occupied v0 Reviewer accept one exact pending adversarial review",
    ),
]
final class FoundryAcceptAdversarialReviewCommand extends Command
{
    public function __construct(
        private readonly AdversarialReviewAcceptanceService $service,
    ) {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->addArgument("readiness-id", InputArgument::REQUIRED)->addOption(
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
            $result = $this->service->accept(
                (string) $input->getArgument("readiness-id"),
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
            "<info>ADVERSARIAL_REVIEW_ACCEPTED</info> " .
                $result["acceptance_id"],
        );
        $output->writeln("Candidate: " . $result["candidate_id"]);
        $output->writeln(
            "Persona approval, admission, and execution: NOT GRANTED",
        );
        return self::SUCCESS;
    }
}
