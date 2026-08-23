<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Guildhall\GuildhallPersonnelDispositionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:guildhall:resolve-inventory', description: 'Issue the Personnel Disposition and bounded Foundry Persona demands')]
final class GuildhallResolveInventoryCommand extends Command
{
    public function __construct(private readonly GuildhallPersonnelDispositionService $service) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('response-id', InputArgument::REQUIRED, 'Exact authoritative Garrison response')->addOption('json', null, InputOption::VALUE_NONE, 'Emit complete disposition and demands'); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try { $result = $this->service->resolve((string) $input->getArgument('response-id')); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        if ((bool) $input->getOption('json')) { $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)); return self::SUCCESS; }
        $disposition = $result['disposition']; $output->writeln('<info>PERSONNEL_DISPOSITION_ISSUED</info> '.$disposition['disposition_id']);
        $output->writeln('Disposition: '.$disposition['disposition']);
        foreach ($result['demands'] as $demand) $output->writeln('- '.$demand['profession'].' '.$demand['status'].' '.$demand['demand_id']);
        $output->writeln('Construction authority: NOT GRANTED'); $output->writeln('Selection authority: NOT GRANTED'); $output->writeln('Execution authority: NOT GRANTED');
        return self::SUCCESS;
    }
}
