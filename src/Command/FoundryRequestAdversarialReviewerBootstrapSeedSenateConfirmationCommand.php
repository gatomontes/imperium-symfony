<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Foundry\AdversarialReviewerBootstrapSeedSenateConfirmationRequestService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[
    AsCommand(
        name: "imperium:foundry:request-reviewer-bootstrap-seed-confirmation",
        description: "Submit the returned initial Reviewer Persona to Senate for manifestation-bound confirmation",
    ),
]
final class FoundryRequestAdversarialReviewerBootstrapSeedSenateConfirmationCommand
    extends Command
{
    public function __construct(
        private readonly AdversarialReviewerBootstrapSeedSenateConfirmationRequestService $service,
    ) {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->addArgument(
            "admission-return-id",
            InputArgument::REQUIRED,
        )->addOption("json", null, InputOption::VALUE_NONE);
    }
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        try {
            $request = $this->service->request(
                (string) $input->getArgument("admission-return-id"),
            );
        } catch (\Throwable $e) {
            $output->writeln("<error>REFUSED</error> " . $e->getMessage());
            return self::FAILURE;
        }
        if ($input->getOption("json")) {
            $output->writeln(
                json_encode(
                    $request,
                    JSON_PRETTY_PRINT |
                        JSON_UNESCAPED_SLASHES |
                        JSON_THROW_ON_ERROR,
                ),
            );
            return self::SUCCESS;
        }
        $output->writeln(
            "<info>ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_SUBMITTED_TO_SENATE</info> " .
                $request["confirmation_request_id"],
        );
        $output->writeln(
            "Persona: " .
                $request["persona_id"] .
                "@" .
                $request["persona_version"],
        );
        $output->writeln(
            "Profile: examination_only; manifestation: sterile witness only",
        );
        $output->writeln(
            "Senate finding, admission, operational spawning, Seat binding, and execution: NOT GRANTED",
        );
        return self::SUCCESS;
    }
}
