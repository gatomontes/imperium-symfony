<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Foundry\ArtificerSeatBindingService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:mastermason:bind-artificer', description: 'Atomically bind the exact qualified Artificer to the Foundry Seat')]
final class MasterMasonBindArtificerCommand extends Command
{
    public function __construct(private readonly ArtificerSeatBindingService $service) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('delivery-id', InputArgument::REQUIRED, 'Exact qualified Artificer delivery')->addOption('json', null, InputOption::VALUE_NONE, 'Emit complete occupancy record'); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try { $occupancy = $this->service->bind((string) $input->getArgument('delivery-id')); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        if ((bool) $input->getOption('json')) { $output->writeln(json_encode($occupancy, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)); return self::SUCCESS; }
        $output->writeln('<info>ARTIFICER_SEAT_BOUND</info> '.$occupancy['binding_id']);
        $output->writeln('Seat: '.$occupancy['seat']); $output->writeln('Manifestation: '.$occupancy['manifestation_id']); $output->writeln('Status: '.$occupancy['status']);
        $output->writeln('Binding atomic: YES'); $output->writeln('Foundry construction authority: GRANTED FOR EXACT AUTHORIZED DEMANDS');
        $output->writeln('Recipient acceptance: NOT RECORDED'); $output->writeln('Execution authority: NOT GRANTED');
        return self::SUCCESS;
    }
}
