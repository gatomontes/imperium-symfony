<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Foundry\AdversarialPersonaReviewService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[
    AsCommand(
        name: "imperium:foundry:perform-adversarial-review",
        description: "Perform the exact accepted adversarial review and seal its disposition",
    ),
]
final class FoundryPerformAdversarialReviewCommand extends Command
{
    public function __construct(
        private readonly AdversarialPersonaReviewService $service,
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
            $result = $this->service->review(
                (string) $input->getArgument("acceptance-id"),
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
            "<info>ADVERSARIAL_REVIEW_SEALED</info> " . $result["result_id"],
        );
        $output->writeln("Disposition: " . $result["decision"]["disposition"]);
        $output->writeln("Status: " . $result["status"]);
        $output->writeln(
            "Persona approval, admission, and execution: NOT GRANTED",
        );
        return self::SUCCESS;
    }
}
