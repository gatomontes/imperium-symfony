<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Curia\SubordinateConstructionAuthorizationRequestService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:curia:request-subordinate-construction', description: 'Present exact sealed subordinate requirements to Imperator for construction authorization')]
final class CuriaRequestSubordinateConstructionCommand extends Command
{
    public function __construct(private readonly SubordinateConstructionAuthorizationRequestService $service) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('resolution-ids', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Exact Hagiography or Studium subordinate-resolution identities')->addOption('json', null, InputOption::VALUE_NONE, 'Emit the complete request'); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try { $request = $this->service->request($input->getArgument('resolution-ids')); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        if ((bool) $input->getOption('json')) { $output->writeln(json_encode($request, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)); return self::SUCCESS; }
        $output->writeln('<info>SUBORDINATE_CONSTRUCTION_AUTHORIZATION_REQUESTED</info> '.$request['request_id']);
        foreach ($request['resolutions'] as $resolution) { $output->writeln('- '.$resolution['office'].' '.$resolution['subordinate_staff_class'].' '.$resolution['resolution_id']); foreach ($resolution['required_specializations'] as $specialization) $output->writeln('  - '.$specialization); }
        $output->writeln('Status: '.$request['status']); $output->writeln('Construction authority: NOT GRANTED');
        $output->writeln('Selection authority: NOT GRANTED'); $output->writeln('Spawning authority: NOT GRANTED'); $output->writeln('Execution authority: NOT GRANTED');
        return self::SUCCESS;
    }
}
