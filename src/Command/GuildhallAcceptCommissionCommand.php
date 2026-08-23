<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Guildhall\GuildhallCommissionAcceptanceService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:guildhall:accept-commission', description: 'Have the bound Guildmaster accept an exact planning commission')]
final class GuildhallAcceptCommissionCommand extends Command
{
    public function __construct(private readonly GuildhallCommissionAcceptanceService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('commission-id', InputArgument::REQUIRED, 'Exact delivered Guildhall planning commission identifier')
            ->addArgument('binding-id', InputArgument::REQUIRED, 'Exact atomic Guildhall binding identifier')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the complete acceptance record');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $acceptance = $this->service->accept(
                (string) $input->getArgument('commission-id'),
                (string) $input->getArgument('binding-id'),
            );
        } catch (\Throwable $exception) {
            $output->writeln('<error>REFUSED</error> '.$exception->getMessage());

            return self::FAILURE;
        }
        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($acceptance, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }
        $output->writeln('<info>GUILDHALL_COMMISSION_ACCEPTED</info> '.$acceptance['acceptance_id']);
        $output->writeln('Guildmaster: '.$acceptance['actor']['manifestation_id']);
        $output->writeln('Disposition: '.$acceptance['disposition']);
        $output->writeln('Deliberation authority: GRANTED FOR EXACT COMMISSION');
        $output->writeln('Personnel disposition authority: GRANTED FOR EXACT COMMISSION');
        $output->writeln('Spawning authority: NOT GRANTED');
        $output->writeln('Execution authority: NOT GRANTED');

        return self::SUCCESS;
    }
}
