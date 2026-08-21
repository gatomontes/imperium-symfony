<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Garrison\PersonaReservationDispositionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:garrison:decide-persona-reservation', description: 'Reserve or factually refuse one exact authorized Persona')]
final class GarrisonDecidePersonaReservationCommand extends Command
{
    public function __construct(private readonly PersonaReservationDispositionService $service) { parent::__construct(); }

    protected function configure(): void
    {
        $this->addArgument('request-id', InputArgument::REQUIRED, 'Exact Guildhall Persona reservation request')
            ->addArgument('constable-binding-id', InputArgument::REQUIRED, 'Exact active Constable binding')
            ->addOption('json', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $disposition = $this->service->decide((string) $input->getArgument('request-id'), (string) $input->getArgument('constable-binding-id'));
        } catch (\Throwable $exception) {
            $output->writeln('<error>REFUSED</error> '.$exception->getMessage());
            return self::FAILURE;
        }
        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($disposition, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            return self::SUCCESS;
        }
        $output->writeln('<info>PERSONA_RESERVATION_DISPOSITION_RECORDED</info> '.$disposition['disposition_id']);
        $output->writeln('Disposition: '.$disposition['disposition']);
        $output->writeln('Status: '.$disposition['status']);
        $output->writeln('Profile derivation authority: NOT GRANTED');
        $output->writeln('Execution authority: NOT GRANTED');
        return self::SUCCESS;
    }
}
