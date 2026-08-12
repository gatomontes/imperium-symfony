<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Foundry\ArtificerConstructionCommissionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:mastermason:commission-artificer', description: 'Issue the exact canonical Artificer construction commission to Conscription')]
final class MasterMasonCommissionArtificerCommand extends Command
{
    public function __construct(private readonly ArtificerConstructionCommissionService $service) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('provisioning-case-id', InputArgument::REQUIRED); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try { $commission = $this->service->issue((string) $input->getArgument('provisioning-case-id')); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        $output->writeln('<info>ARTIFICER_CONSTRUCTION_COMMISSION_ISSUED</info> '.$commission['commission_id']);
        $output->writeln('Target: '.$commission['target_seat']); $output->writeln('Status: '.$commission['status']);
        $output->writeln('Spawning authority: GRANTED FOR EXACT COMMISSION');
        $output->writeln('Foundry construction authority: NOT GRANTED TO CONSCRIPTION');
        $output->writeln('Seat binding authority: NOT GRANTED'); $output->writeln('Recipient acceptance: NOT RECORDED'); $output->writeln('Execution authority: NOT GRANTED');
        return self::SUCCESS;
    }
}
