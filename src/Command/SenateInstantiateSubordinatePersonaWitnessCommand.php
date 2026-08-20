<?php
declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Senate\SubordinatePersonaWitnessInstantiationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: "imperium:senate:instantiate-subordinate-persona-witness",
    description: "Instantiate the exact Persona as a sterile Senate witness",
)]
final class SenateInstantiateSubordinatePersonaWitnessCommand extends Command
{
    public function __construct(private readonly SubordinatePersonaWitnessInstantiationService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument("confirmation-acceptance-id", InputArgument::REQUIRED)
            ->addOption("json", null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $record = $this->service->instantiate((string) $input->getArgument("confirmation-acceptance-id"));
        } catch (\Throwable $exception) {
            $output->writeln("<error>REFUSED</error> " . $exception->getMessage());
            return self::FAILURE;
        }
        $output->writeln($input->getOption("json")
            ? json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            : "<info>SUBORDINATE_PERSONA_WITNESS_INSTANTIATED</info> " . $record["manifestation_id"]);
        return self::SUCCESS;
    }
}
