<?php
declare(strict_types=1);
namespace App\Command;
use App\Imperium\Runtime\Foundry\BlackquillProductionAuthorizationAcceptanceService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
#[
    AsCommand(
        name: "imperium:foundry:accept-blackquill-production",
        description: "Have the occupied Artificer accept exact Blackquill production-processing authority",
    ),
]
final class FoundryAcceptBlackquillProductionAuthorizationCommand extends
    Command
{
    public function __construct(
        private readonly BlackquillProductionAuthorizationAcceptanceService $service,
    ) {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->addArgument("delivery-id", InputArgument::REQUIRED)
            ->addArgument("binding-id", InputArgument::REQUIRED)
            ->addOption("json", null, InputOption::VALUE_NONE);
    }
    protected function execute(InputInterface $i, OutputInterface $o): int
    {
        try {
            $r = $this->service->accept(
                (string) $i->getArgument("delivery-id"),
                (string) $i->getArgument("binding-id"),
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
            "<info>BLACKQUILL_PRODUCTION_AUTHORIZATION_ACCEPTED</info> " .
                $r["acceptance_id"],
        );
        $o->writeln("Production authority: EXERCISABLE FOR EXACT CASE");
        $o->writeln(
            "Review findings and every downstream authority: NOT GRANTED",
        );
        return self::SUCCESS;
    }
}
