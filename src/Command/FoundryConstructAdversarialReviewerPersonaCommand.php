<?php
declare(strict_types=1);
namespace App\Command;
use App\Imperium\Runtime\Foundry\AdversarialReviewerPersonaConstructionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
#[
    AsCommand(
        name: "imperium:foundry:construct-adversarial-reviewer-persona",
        description: "Construct and seal the exact versioned Adversarial Reviewer Persona candidate",
    ),
]
final class FoundryConstructAdversarialReviewerPersonaCommand extends Command
{
    public function __construct(
        private readonly AdversarialReviewerPersonaConstructionService $service,
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
            $candidate = $this->service->construct(
                (string) $input->getArgument("acceptance-id"),
            );
        } catch (\Throwable $e) {
            $output->writeln("<error>REFUSED</error> " . $e->getMessage());
            return self::FAILURE;
        }
        if ($input->getOption("json")) {
            $output->writeln(
                json_encode(
                    $candidate,
                    JSON_PRETTY_PRINT |
                        JSON_UNESCAPED_SLASHES |
                        JSON_THROW_ON_ERROR,
                ),
            );
            return self::SUCCESS;
        }
        $output->writeln(
            "<info>ADVERSARIAL_REVIEWER_PERSONA_CANDIDATE_SEALED</info> " .
                $candidate["persona_candidate_id"],
        );
        $output->writeln(
            "Persona: " .
                $candidate["persona_id"] .
                "@" .
                $candidate["persona_version"],
        );
        $output->writeln("Status: " . $candidate["status"]);
        $output->writeln(
            "Admission, spawning, Seat binding, review, and candidate approval: NOT GRANTED",
        );
        return self::SUCCESS;
    }
}
