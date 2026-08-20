<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Senate\SubordinatePersonaDepositionOpeningService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: "imperium:senate:open-subordinate-persona-deposition",
    description: "Open the secured deposition of an exact Senate Persona witness",
)]
final class SenateOpenSubordinatePersonaDepositionCommand extends Command
{
    public function __construct(private readonly SubordinatePersonaDepositionOpeningService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument("manifestation-id", InputArgument::REQUIRED)
            ->addArgument("confirmation-plan-json", InputArgument::REQUIRED)
            ->addOption("json", null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $path = (string) $input->getArgument("confirmation-plan-json");
            $plan = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            $record = $this->service->open(
                (string) $input->getArgument("manifestation-id"),
                $plan,
            );
        } catch (\Throwable $exception) {
            $output->writeln("<error>REFUSED</error> " . $exception->getMessage());
            return self::FAILURE;
        }
        $output->writeln($input->getOption("json")
            ? json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            : "<info>SUBORDINATE_PERSONA_DEPOSITION_OPENED</info> " . $record["deposition_id"]);
        return self::SUCCESS;
    }
}
