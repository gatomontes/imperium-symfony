<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Guildhall\PersonnelUseAuthorizationAcceptanceService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:guildhall:accept-personnel-use', description: 'Accept exact authorized personnel and request Garrison reservation')]
final class GuildhallAcceptPersonnelUseAuthorizationCommand extends Command
{
    public function __construct(private readonly PersonnelUseAuthorizationAcceptanceService $service) { parent::__construct(); }

    protected function configure(): void
    {
        $this->addArgument('act-id', InputArgument::REQUIRED, 'Exact authorized Imperator personnel-use act')
            ->addOption('json', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $result = $this->service->accept((string) $input->getArgument('act-id'));
        } catch (\Throwable $exception) {
            $output->writeln('<error>REFUSED</error> '.$exception->getMessage());
            return self::FAILURE;
        }
        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            return self::SUCCESS;
        }
        $output->writeln('<info>AUTHORIZED_PERSONNEL_ACCEPTED</info> '.$result['acceptance']['acceptance_id']);
        $output->writeln('Reservation requests: '.count($result['reservation_requests']));
        $output->writeln('Reservation authority: NOT GRANTED TO GUILDHALL');
        $output->writeln('Profile derivation authority: NOT GRANTED');
        return self::SUCCESS;
    }
}
