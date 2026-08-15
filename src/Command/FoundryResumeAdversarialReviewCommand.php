<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Foundry\AdversarialReviewReadinessService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[
    AsCommand(
        name: "imperium:foundry:resume-adversarial-review",
        description: "Resume an exact waiting review after operator-root Reviewer installation",
    ),
]
final class FoundryResumeAdversarialReviewCommand extends Command
{
    public function __construct(
        private readonly AdversarialReviewReadinessService $service,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument("demand-id", InputArgument::REQUIRED)->addOption(
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
            $result = $this->service->resume(
                (string) $input->getArgument("demand-id"),
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
            "<info>ADVERSARIAL_REVIEW_READY</info> " . $result["readiness_id"],
        );
        $output->writeln("Status: " . $result["status"]);
        $output->writeln(
            "Initial Reviewer provenance: OPERATOR_ROOT_INSTALLATION",
        );
        return self::SUCCESS;
    }
}
