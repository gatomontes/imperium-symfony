<?php
declare(strict_types=1);
namespace App\Command;
use App\Imperium\Runtime\Foundry\BlackquillExternalReviewerIntakeService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
#[
    AsCommand(
        name: "imperium:foundry:intake-blackquill-reviewer",
        description: "Seal a Blackquill-derived external Reviewer Persona intake for the bootstrap review case",
    ),
]
final class FoundryIntakeBlackquillReviewerCommand extends Command
{
    public function __construct(
        private readonly BlackquillExternalReviewerIntakeService $service,
    ) {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->addArgument(
            "review-case-id",
            InputArgument::REQUIRED,
        )->addOption("json", null, InputOption::VALUE_NONE);
    }
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        try {
            $intake = $this->service->intake(
                (string) $input->getArgument("review-case-id"),
            );
        } catch (\Throwable $e) {
            $output->writeln("<error>REFUSED</error> " . $e->getMessage());
            return self::FAILURE;
        }
        if ($input->getOption("json")) {
            $output->writeln(
                json_encode(
                    $intake,
                    JSON_PRETTY_PRINT |
                        JSON_UNESCAPED_SLASHES |
                        JSON_THROW_ON_ERROR,
                ),
            );
            return self::SUCCESS;
        }
        $output->writeln(
            "<info>BLACKQUILL_EXTERNAL_REVIEWER_INTAKE_SEALED</info> " .
                $intake["intake_id"],
        );
        $output->writeln("Status: " . $intake["status"]);
        $output->writeln("Garrison admission: UNVERIFIED");
        $output->writeln("Review occupation and authority: NOT GRANTED");
        return self::SUCCESS;
    }
}
