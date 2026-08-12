<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Foundry\FoundryAuthorizationAcceptanceService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:foundry:accept-authorization', description: 'Have the bound Artificer accept the exact Foundry construction authorization')]
final class FoundryAcceptAuthorizationCommand extends Command
{
    public function __construct(private readonly FoundryAuthorizationAcceptanceService $service) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('delivery-id', InputArgument::REQUIRED, 'Exact Foundry construction authorization delivery')->addArgument('binding-id', InputArgument::REQUIRED, 'Exact Artificer Seat binding')->addOption('json', null, InputOption::VALUE_NONE, 'Emit complete acceptance record'); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try { $acceptance = $this->service->accept((string) $input->getArgument('delivery-id'), (string) $input->getArgument('binding-id')); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        if ((bool) $input->getOption('json')) { $output->writeln(json_encode($acceptance, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)); return self::SUCCESS; }
        $output->writeln('<info>FOUNDRY_AUTHORIZATION_ACCEPTED</info> '.$acceptance['acceptance_id']);
        $output->writeln('Artificer: '.$acceptance['actor']['manifestation_id']); $output->writeln('Disposition: '.$acceptance['disposition']);
        $output->writeln('Construction authority: GRANTED FOR EXACT AUTHORIZED DEMANDS'); $output->writeln('Persona selection authority: NOT GRANTED');
        $output->writeln('Spawning authority: NOT GRANTED'); $output->writeln('Execution authority: NOT GRANTED');
        return self::SUCCESS;
    }
}
