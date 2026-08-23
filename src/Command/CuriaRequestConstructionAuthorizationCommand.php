<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Curia\ConstructionAuthorizationRequestService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:curia:request-construction', description: 'Present exact Foundry Persona demands to Imperator for construction authorization')]
final class CuriaRequestConstructionAuthorizationCommand extends Command
{
    public function __construct(private readonly ConstructionAuthorizationRequestService $service) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('disposition-id', InputArgument::REQUIRED, 'Exact Guildhall Personnel Disposition')->addOption('json', null, InputOption::VALUE_NONE, 'Emit complete authorization request'); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try { $request = $this->service->request((string) $input->getArgument('disposition-id')); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        if ((bool) $input->getOption('json')) { $output->writeln(json_encode($request, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)); return self::SUCCESS; }
        $output->writeln('<info>CONSTRUCTION_AUTHORIZATION_REQUESTED</info> '.$request['request_id']); $output->writeln('Imperator: '.$request['recipient']['id']);
        foreach ($request['demands'] as $demand) $output->writeln('- '.$demand['profession'].' '.$demand['demand_id']);
        $output->writeln('Status: '.$request['status']); $output->writeln('Construction authority: NOT GRANTED');
        $output->writeln('Selection authority: NOT GRANTED'); $output->writeln('Execution authority: NOT GRANTED');
        return self::SUCCESS;
    }
}
