<?php
declare(strict_types=1);
namespace App\Command;
use App\Imperium\Runtime\Foundry\AdversarialReviewerPersonaReviewInitiationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
#[
    AsCommand(
        name: "imperium:foundry:initiate-adversarial-reviewer-persona-review",
        description: "Initiate independent review of the Adversarial Reviewer Persona candidate and expose bootstrap constraints",
    ),
]
final class FoundryInitiateAdversarialReviewerPersonaReviewCommand extends
    Command
{
    public function __construct(
        private readonly AdversarialReviewerPersonaReviewInitiationService $service,
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
            $case = $this->service->initiate(
                (string) $input->getArgument("candidate-id"),
            );
        } catch (\Throwable $e) {
            $output->writeln("<error>REFUSED</error> " . $e->getMessage());
            return self::FAILURE;
        }
        if ($input->getOption("json")) {
            $output->writeln(
                json_encode(
                    $case,
                    JSON_PRETTY_PRINT |
                        JSON_UNESCAPED_SLASHES |
                        JSON_THROW_ON_ERROR,
                ),
            );
            return self::SUCCESS;
        }
        $output->writeln(
            "<info>ADVERSARIAL_REVIEWER_PERSONA_REVIEW_INITIATED</info> " .
                $case["review_case_id"],
        );
        $output->writeln("Status: " . $case["status"]);
        $output->writeln("Escalation: " . $case["escalation_recipient"]);
        $output->writeln(
            "Self-review, pre-admission occupation, and review waiver: PROHIBITED",
        );
        return self::SUCCESS;
    }
}
