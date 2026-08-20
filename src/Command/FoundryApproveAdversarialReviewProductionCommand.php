<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Foundry\AdversarialReviewProductionApprovalService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[
    AsCommand(
        name: "imperium:foundry:approve-adversarial-review-production",
        description: "Approve the exact passed Persona candidate for admission delivery",
    ),
]
final class FoundryApproveAdversarialReviewProductionCommand extends Command
{
    public function __construct(
        private readonly AdversarialReviewProductionApprovalService $service,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument("result-id", InputArgument::REQUIRED)
            ->addArgument("artificer-binding-id", InputArgument::REQUIRED)
            ->addOption("json", null, InputOption::VALUE_NONE);
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        try {
            $record = $this->service->approve(
                (string) $input->getArgument("result-id"),
                (string) $input->getArgument("artificer-binding-id"),
            );
        } catch (\Throwable $exception) {
            $output->writeln(
                "<error>REFUSED</error> " . $exception->getMessage(),
            );
            return self::FAILURE;
        }
        $output->writeln(
            $input->getOption("json")
                ? json_encode(
                    $record,
                    JSON_PRETTY_PRINT |
                        JSON_UNESCAPED_SLASHES |
                        JSON_THROW_ON_ERROR,
                )
                : "<info>SUBORDINATE_PERSONA_PRODUCTION_APPROVED</info> " .
                    $record["production_approval_id"],
        );
        return self::SUCCESS;
    }
}
