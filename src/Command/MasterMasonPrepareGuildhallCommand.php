<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Guildhall\GuildhallProvisioningCaseService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:mastermason:prepare-guildhall', description: 'Open a non-authorizing Guildhall provisioning case')]
final class MasterMasonPrepareGuildhallCommand extends Command
{
    public function __construct(private readonly GuildhallProvisioningCaseService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('demand-id', InputArgument::REQUIRED, 'Exact Guildhall activation demand identifier')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the complete provisioning case');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $case = $this->service->open((string) $input->getArgument('demand-id'));
        } catch (\Throwable $exception) {
            $output->writeln('<error>REFUSED</error> '.$exception->getMessage());

            return self::FAILURE;
        }
        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($case, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        $output->writeln('<info>GUILDHALL_PROVISIONING_OPENED</info> '.$case['case_id']);
        foreach ($case['lanes'] as $lane) {
            $output->writeln('- '.$lane['seat'].' '.$lane['canonical_staff_requirement']['status']);
        }
        $output->writeln('Status: '.$case['status']);
        $output->writeln('Summons: Seneschal request via Chamberlain REQUIRED');
        $output->writeln('Mission Persona selection: NOT REQUIRED');
        $output->writeln('Per-mission Profile derivation: NOT REQUIRED');
        $output->writeln('Spawning authority: NOT GRANTED');
        $output->writeln('Recipient acceptance: NOT RECORDED');
        $output->writeln('Execution authority: NOT GRANTED');

        return self::SUCCESS;
    }
}
