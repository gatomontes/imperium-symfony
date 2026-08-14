<?php
declare(strict_types=1);
namespace App\Command;
use App\Imperium\Runtime\Curia\BlackquillProductionAuthorizationRequestService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
#[
    AsCommand(
        name: "imperium:curia:request-blackquill-production",
        description: "Present the exact Blackquill production-remediation case to Imperator",
    ),
]
final class CuriaRequestBlackquillProductionAuthorizationCommand extends Command
{
    public function __construct(
        private readonly BlackquillProductionAuthorizationRequestService $service,
    ) {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->addArgument("case-id", InputArgument::REQUIRED)->addOption(
            "json",
            null,
            InputOption::VALUE_NONE,
        );
    }
    protected function execute(InputInterface $i, OutputInterface $o): int
    {
        try {
            $r = $this->service->request((string) $i->getArgument("case-id"));
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
            "<info>BLACKQUILL_PRODUCTION_AUTHORIZATION_REQUESTED</info> " .
                $r["request_id"],
        );
        $o->writeln("Status: " . $r["status"]);
        $o->writeln("Production and every downstream authority: NOT GRANTED");
        return self::SUCCESS;
    }
}
