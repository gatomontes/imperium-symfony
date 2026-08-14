<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Foundry\AdversarialReviewerBootstrapSeedAdmissionDeliveryService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[
    AsCommand(
        name: "imperium:foundry:deliver-adversarial-reviewer-bootstrap-seed",
        description: "Deliver the production-approved initial Adversarial Reviewer Persona to Garrison for admission",
    ),
]
final class FoundryDeliverAdversarialReviewerBootstrapSeedForAdmissionCommand
    extends Command
{
    public function __construct(
        private readonly AdversarialReviewerBootstrapSeedAdmissionDeliveryService $service,
    ) {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->addArgument(
            "production-approval-id",
            InputArgument::REQUIRED,
        )->addOption("json", null, InputOption::VALUE_NONE);
    }
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        try {
            $delivery = $this->service->deliver(
                (string) $input->getArgument("production-approval-id"),
            );
        } catch (\Throwable $e) {
            $output->writeln("<error>REFUSED</error> " . $e->getMessage());
            return self::FAILURE;
        }
        if ($input->getOption("json")) {
            $output->writeln(
                json_encode(
                    $delivery,
                    JSON_PRETTY_PRINT |
                        JSON_UNESCAPED_SLASHES |
                        JSON_THROW_ON_ERROR,
                ),
            );
            return self::SUCCESS;
        }
        $output->writeln(
            "<info>ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_DELIVERED_TO_GARRISON</info> " .
                $delivery["delivery_id"],
        );
        $output->writeln(
            "Persona: " .
                $delivery["persona_id"] .
                "@" .
                $delivery["persona_version"],
        );
        $output->writeln("Admission decision: PENDING GARRISON");
        $output->writeln(
            "Admission, profile, spawning, Seat binding, and execution authority: NOT GRANTED",
        );
        return self::SUCCESS;
    }
}
