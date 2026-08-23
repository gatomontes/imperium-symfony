<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Foundry\PersonaProductionCaseService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:foundry:open-production-cases', description: 'Open one bounded Persona production case per accepted construction demand')]
final class FoundryOpenProductionCasesCommand extends Command
{
    public function __construct(private readonly PersonaProductionCaseService $service) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('acceptance-id', InputArgument::REQUIRED, 'Exact Foundry authorization acceptance')->addOption('json', null, InputOption::VALUE_NONE, 'Emit complete production-case records'); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try { $result = $this->service->open((string) $input->getArgument('acceptance-id')); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        if ((bool) $input->getOption('json')) { $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)); return self::SUCCESS; }
        $output->writeln('<info>PERSONA_PRODUCTION_CASES_OPENED</info> '.count($result['cases']));
        $output->writeln('Artificer: '.$result['artificer']['manifestation_id']);
        foreach ($result['cases'] as $case) $output->writeln('- '.$case['queue_position'].' '.$case['profession'].' '.$case['case_id'].' '.$case['status']);
        $output->writeln('Persona selection authority: NOT GRANTED'); $output->writeln('Spawning authority: NOT GRANTED');
        $output->writeln('Admission authority: NOT GRANTED'); $output->writeln('Execution authority: NOT GRANTED');
        return self::SUCCESS;
    }
}
