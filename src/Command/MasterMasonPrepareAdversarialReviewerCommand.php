<?php
declare(strict_types=1);
namespace App\Command;
use App\Imperium\Runtime\Foundry\AdversarialReviewerProvisioningCaseService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
#[
    AsCommand(
        name: "imperium:mastermason:prepare-adversarial-reviewer",
        description: "Open a non-authorizing exact Adversarial Reviewer provisioning case",
    ),
]
final class MasterMasonPrepareAdversarialReviewerCommand extends Command
{
    public function __construct(
        private readonly AdversarialReviewerProvisioningCaseService $s,
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
    protected function execute(InputInterface $i, OutputInterface $o): int
    {
        try {
            $r = $this->s->open((string) $i->getArgument("demand-id"));
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
                "<info>ADVERSARIAL_REVIEWER_PROVISIONING_OPENED</info> " .
                    $r["case_id"],
            );
            $o->writeln("Status: " . $r["status"]);
            $o->writeln("Reviewer Persona construction: REQUIRED");
            $o->writeln("Spawning authority: NOT GRANTED");
        }
        return self::SUCCESS;
    }
}
