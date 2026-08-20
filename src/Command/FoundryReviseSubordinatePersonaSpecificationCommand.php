<?php
declare(strict_types=1);
namespace App\Command;
use App\Imperium\Runtime\Foundry\SubordinatePersonaSpecificationRevisionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
#[
    AsCommand(
        name: "imperium:foundry:revise-subordinate-persona-specification",
        description: "Supersede a Persona specification from an exact clarification or adversarial-correction return",
    ),
]
final class FoundryReviseSubordinatePersonaSpecificationCommand extends Command
{
    public function __construct(
        private readonly SubordinatePersonaSpecificationRevisionService $s,
    ) {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->addArgument(
            "revision-return-id",
            InputArgument::REQUIRED,
        )->addOption("json", null, InputOption::VALUE_NONE);
    }
    protected function execute(InputInterface $i, OutputInterface $o): int
    {
        try {
            $r = $this->s->revise(
                (string) $i->getArgument("revision-return-id"),
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
        } else {
            $o->writeln(
                "<info>SUBORDINATE_PERSONA_SPECIFICATION_REVISED</info> " .
                    $r["specification_id"],
            );
            $o->writeln("Version: " . $r["specification_version"]);
            $o->writeln("Supersedes: " . $r["supersedes"]["specification_id"]);
        }
        return self::SUCCESS;
    }
}
