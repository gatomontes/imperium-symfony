<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Foundry\AdversarialReviewCorrectionReturnService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[
    AsCommand(
        name: "imperium:foundry:return-adversarial-review-correction",
        description: "Return an exact failed adversarial review for versioned specification revision",
    ),
]
final class FoundryReturnAdversarialReviewCorrectionCommand extends Command
{
    public function __construct(
        private readonly AdversarialReviewCorrectionReturnService $service,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument("result-id", InputArgument::REQUIRED)->addOption(
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
            $record = $this->service->returnForRevision(
                (string) $input->getArgument("result-id"),
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
                : "<info>ADVERSARIAL_REVIEW_RETURNED_FOR_VERSIONED_REVISION</info> " .
                    $record["return_id"],
        );
        return self::SUCCESS;
    }
}
