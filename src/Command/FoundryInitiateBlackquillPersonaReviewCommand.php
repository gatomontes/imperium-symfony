<?php
declare(strict_types=1);
namespace App\Command;
use App\Imperium\Runtime\Foundry\BlackquillPersonaReviewInitiationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
#[
    AsCommand(
        name: "imperium:foundry:initiate-blackquill-persona-review",
        description: "Initiate independent review of the Blackquill Persona candidate and expose its independence constraint",
    ),
]
final class FoundryInitiateBlackquillPersonaReviewCommand extends Command
{
    public function __construct(
        private readonly BlackquillPersonaReviewInitiationService $service,
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
            "<info>BLACKQUILL_PERSONA_REVIEW_INITIATED</info> " .
                $case["review_case_id"],
        );
        $output->writeln("Status: " . $case["status"]);
        $output->writeln("Escalation: " . $case["escalation_recipient"]);
        $output->writeln(
            "Blackquill self-review and every downstream authority: PROHIBITED",
        );
        return self::SUCCESS;
    }
}
