<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Garrison\GarrisonInventoryInquiryService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:guildhall:query-garrison', description: 'Route an exact Guildhall Profession Determination to Garrison inventory')]
final class GarrisonRouteInventoryInquiryCommand extends Command
{
    public function __construct(private readonly GarrisonInventoryInquiryService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('determination-id', InputArgument::REQUIRED, 'Exact sealed Guildhall Profession Determination identifier')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the complete Garrison inquiry');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $inquiry = $this->service->route((string) $input->getArgument('determination-id'));
        } catch (\Throwable $exception) {
            $output->writeln('<error>REFUSED</error> '.$exception->getMessage());
            return self::FAILURE;
        }
        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($inquiry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            return self::SUCCESS;
        }
        $output->writeln('<info>GARRISON_INVENTORY_INQUIRY_RECORDED</info> '.$inquiry['inquiry_id']);
        $output->writeln('Status: '.$inquiry['status']);
        $output->writeln('Questions: '.count($inquiry['inventory_questions']));
        $output->writeln('Authoritative inventory response: NOT ISSUED');
        $output->writeln('Selection authority: NOT GRANTED');
        $output->writeln('Execution authority: NOT GRANTED');
        return self::SUCCESS;
    }
}
