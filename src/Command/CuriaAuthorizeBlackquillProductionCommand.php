<?php
declare(strict_types=1);
namespace App\Command;
use App\Imperium\Runtime\Curia\BlackquillProductionAuthorizationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
#[
    AsCommand(
        name: "imperium:curia:authorize-blackquill-production",
        description: "Record and deliver exact Blackquill Persona production-processing authority",
    ),
]
final class CuriaAuthorizeBlackquillProductionCommand extends Command
{
    public function __construct(
        private readonly BlackquillProductionAuthorizationService $service,
    ) {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->addArgument("request-id", InputArgument::REQUIRED)->addOption(
            "json",
            null,
            InputOption::VALUE_NONE,
        );
    }
    protected function execute(InputInterface $i, OutputInterface $o): int
    {
        try {
            $r = $this->service->authorize(
                (string) $i->getArgument("request-id"),
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
            "<info>BLACKQUILL_PRODUCTION_AUTHORIZED</info> " .
                $r["authorization_act_id"],
        );
        $o->writeln("Status: " . $r["status"]);
        $o->writeln("Production authority: PENDING FOUNDRY ACCEPTANCE");
        $o->writeln(
            "Review findings and every downstream authority: NOT GRANTED",
        );
        return self::SUCCESS;
    }
}
