<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Curia\AdversarialReviewerConstructionAuthorizationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[
    AsCommand(
        name: "imperium:curia:authorize-adversarial-reviewer-construction",
        description: "Deliver exact Adversarial Reviewer Persona construction authority to the Foundry",
    ),
]
final class CuriaAuthorizeAdversarialReviewerConstructionCommand extends Command
{
    public function __construct(
        private readonly AdversarialReviewerConstructionAuthorizationService $service,
    ) {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->addArgument("case-id", InputArgument::REQUIRED)->addOption(
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
            $delivery = $this->service->authorize(
                (string) $input->getArgument("case-id"),
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
            "<info>ADVERSARIAL_REVIEWER_CONSTRUCTION_AUTHORIZED</info> " .
                $delivery["authorization_act_id"],
        );
        $output->writeln("Delivery: " . $delivery["delivery_id"]);
        $output->writeln("Status: " . $delivery["status"]);
        $output->writeln("Construction authority: PENDING FOUNDRY ACCEPTANCE");
        $output->writeln(
            "Admission, spawning, Seat binding, review, and candidate approval: NOT GRANTED",
        );
        return self::SUCCESS;
    }
}
