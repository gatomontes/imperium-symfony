<?php
declare(strict_types=1);
namespace App\Command;
use App\Imperium\Runtime\Foundry\BlackquillPersonaCandidateService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
#[
    AsCommand(
        name: "imperium:foundry:seal-blackquill-persona-candidate",
        description: "Seal the immutable Blackquill Persona Candidate from accepted production authority",
    ),
]
final class FoundrySealBlackquillPersonaCandidateCommand extends Command
{
    public function __construct(
        private readonly BlackquillPersonaCandidateService $service,
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
    protected function execute(InputInterface $i, OutputInterface $o): int
    {
        try {
            $r = $this->service->seal(
                (string) $i->getArgument("acceptance-id"),
            );
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
            return self::SUCCESS;
        }
        $o->writeln(
            "<info>BLACKQUILL_PERSONA_CANDIDATE_SEALED</info> " .
                $r["persona_candidate_id"],
        );
        $o->writeln(
            "Persona: " . $r["persona_id"] . "@" . $r["persona_version"],
        );
        $o->writeln("Status: " . $r["status"]);
        $o->writeln(
            "Review findings and every downstream authority: NOT GRANTED",
        );
        return self::SUCCESS;
    }
}
