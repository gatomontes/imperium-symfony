<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Garrison\GarrisonInventoryResponseService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:garrison:answer-inquiry', description: 'Return authoritative exact inventory facts to Guildhall')]
final class GarrisonAnswerInventoryInquiryCommand extends Command
{
    public function __construct(private readonly GarrisonInventoryResponseService $service) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('inquiry-id', InputArgument::REQUIRED, 'Exact pending Garrison inventory inquiry')->addOption('json', null, InputOption::VALUE_NONE, 'Emit complete inventory response'); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try { $response = $this->service->respond((string) $input->getArgument('inquiry-id')); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        if ((bool) $input->getOption('json')) { $output->writeln(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)); return self::SUCCESS; }
        $output->writeln('<info>GARRISON_INVENTORY_FACTS_DELIVERED</info> '.$response['response_id']);
        $output->writeln('Constable: '.$response['responder']['manifestation_id']); $output->writeln('Finding: '.$response['ledger_finding']);
        $output->writeln('Custody records: '.count($response['inventory_records'])); $output->writeln('Ranking authority: NOT GRANTED');
        $output->writeln('Selection authority: NOT GRANTED'); $output->writeln('Execution authority: NOT GRANTED');
        return self::SUCCESS;
    }
}
