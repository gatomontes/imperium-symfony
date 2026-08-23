<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Curia\SubordinateConstructionAuthorizationDecisionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:curia:authorize-subordinate-construction', description: 'Record Imperator authorization for an exact sealed subordinate-resolution set')]
final class CuriaAuthorizeSubordinateConstructionCommand extends Command
{
    public function __construct(private readonly SubordinateConstructionAuthorizationDecisionService $service) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('request-id', InputArgument::REQUIRED)->addOption('act-id', null, InputOption::VALUE_REQUIRED)->addOption('json', null, InputOption::VALUE_NONE); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try { $act = $this->service->authorize((string) $input->getArgument('request-id'), is_string($input->getOption('act-id')) ? $input->getOption('act-id') : null); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        if ((bool) $input->getOption('json')) { $output->writeln(json_encode($act, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)); return self::SUCCESS; }
        $output->writeln('<info>SUBORDINATE_CONSTRUCTION_AUTHORIZED</info> '.$act['act_id']);
        foreach ($act['resolutions'] as $resolution) $output->writeln('- '.$resolution['office'].' '.$resolution['subordinate_staff_class'].' '.$resolution['resolution_id']);
        $output->writeln('Construction authority: GRANTED FOR EXACT RESOLUTIONS'); $output->writeln('Selection authority: NOT GRANTED');
        $output->writeln('Profile approval authority: NOT GRANTED'); $output->writeln('Spawning authority: NOT GRANTED');
        $output->writeln('Seat binding authority: NOT GRANTED'); $output->writeln('Execution authority: NOT GRANTED');
        return self::SUCCESS;
    }
}
