<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Authorship\AuthorshipOfficeActivationDemandService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:hagiography:demand-activation', description: 'Demand the canonical resident Sanctographer for an exact authorship commission')]
final class HagiographyDemandActivationCommand extends Command
{
    public function __construct(private readonly AuthorshipOfficeActivationDemandService $service) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('commission-id', InputArgument::REQUIRED, 'Exact Hagiography authorship commission')->addOption('json', null, InputOption::VALUE_NONE, 'Emit complete activation demand'); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try { $demand = $this->service->demand('hagiography', (string) $input->getArgument('commission-id')); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        if ((bool) $input->getOption('json')) { $output->writeln(json_encode($demand, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)); return self::SUCCESS; }
        $output->writeln('<info>HAGIOGRAPHY_ACTIVATION_REQUIRED</info> '.$demand['demand_id']); $output->writeln('Seat: '.$demand['required_seats'][0]['seat']); $output->writeln('Status: '.$demand['status']);
        $output->writeln('Authorship authority: PRESENT; NOT EXERCISABLE WHILE VACANT'); $output->writeln('Subordinate Chronicler resolution: PENDING SANCTOGRAPHER');
        $output->writeln('Spawning authority: NOT GRANTED'); $output->writeln('Execution authority: NOT GRANTED'); return self::SUCCESS;
    }
}
