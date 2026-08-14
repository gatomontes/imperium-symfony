<?php
declare(strict_types=1);
namespace App\Command;
use App\Imperium\Runtime\Foundry\AdversarialReviewerDemandService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
#[
    AsCommand(
        name: "imperium:foundry:demand-adversarial-reviewer",
        description: "Demand independent Adversarial Reviewer occupation for one exact Persona candidate",
    ),
]
final class FoundryDemandAdversarialReviewerCommand extends Command
{
    public function __construct(
        private readonly AdversarialReviewerDemandService $s,
    ) {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->addArgument("review-id", InputArgument::REQUIRED)->addOption(
            "json",
            null,
            InputOption::VALUE_NONE,
        );
    }
    protected function execute(InputInterface $i, OutputInterface $o): int
    {
        try {
            $r = $this->s->demand((string) $i->getArgument("review-id"));
        } catch (\Throwable $e) {
            $o->writeln("<error>REFUSED</error> " . $e->getMessage());
            return self::FAILURE;
        }
        if ($i->getOption("json")) {
            $o->writeln(
                json_encode(
                    $r,
                    JSON_PRETTY_PRINT |
                        JSON_UNESCAPED_SLASHES |
                        JSON_THROW_ON_ERROR,
                ),
            );
        } else {
            $o->writeln(
                "<info>ADVERSARIAL_REVIEWER_OCCUPATION_REQUIRED</info> " .
                    $r["demand_id"],
            );
            $o->writeln("Candidate: " . $r["candidate_id"]);
            $o->writeln("Status: " . $r["status"]);
            $o->writeln("Review authority: NOT GRANTED UNTIL EXACT OCCUPATION");
        }
        return self::SUCCESS;
    }
}
