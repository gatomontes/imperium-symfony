<?php
declare(strict_types=1);
namespace App\Command;
use App\Imperium\Runtime\Curia\AdversarialReviewerBootstrapSeedAuthorizationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
#[
    AsCommand(
        name: "imperium:imperator:authorize-adversarial-reviewer-bootstrap-seed",
        description: "Authorize the exact initial Reviewer candidate as a non-precedential bootstrap seed",
    ),
]
final class ImperatorAuthorizeAdversarialReviewerBootstrapSeedCommand extends
    Command
{
    public function __construct(
        private readonly AdversarialReviewerBootstrapSeedAuthorizationService $service,
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
            $delivery = $this->service->authorize(
                (string) $input->getArgument("candidate-id"),
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
            "<info>ADVERSARIAL_REVIEWER_BOOTSTRAP_SEED_AUTHORIZED</info> " .
                $delivery["delivery_id"],
        );
        $output->writeln("Status: " . $delivery["status"]);
        $output->writeln(
            "Foundry acceptance and every downstream authority: NOT GRANTED",
        );
        return self::SUCCESS;
    }
}
