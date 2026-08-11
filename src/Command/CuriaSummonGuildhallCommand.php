<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Guildhall\GuildhallSummonsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:curia:summon-guildhall', description: 'Record and route an exact Seneschal Guildhall summons')]
final class CuriaSummonGuildhallCommand extends Command
{
    public function __construct(private readonly GuildhallSummonsService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('provisioning-case-id', InputArgument::REQUIRED, 'Exact ready Guildhall provisioning case identifier')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit the complete summons and construction commissions');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $result = $this->service->summon((string) $input->getArgument('provisioning-case-id'));
        } catch (\Throwable $exception) {
            $output->writeln('<error>REFUSED</error> '.$exception->getMessage());

            return self::FAILURE;
        }
        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }
        $summons = $result['summons'];
        $output->writeln('<info>GUILDHALL_SUMMONED</info> '.$summons['summons_id']);
        $output->writeln('Seneschal: '.$summons['seneschal']['disposition']);
        $output->writeln('Chamberlain: '.$summons['chamberlain']['disposition']);
        foreach ($result['commissions'] as $commission) {
            $output->writeln('- '.$commission['target_seat'].' '.$commission['status']);
        }
        $output->writeln('Spawning authority: GRANTED FOR EXACT COMMISSIONS');
        $output->writeln('Seat binding authority: NOT GRANTED');
        $output->writeln('Recipient acceptance: NOT RECORDED');
        $output->writeln('Execution authority: NOT GRANTED');

        return self::SUCCESS;
    }
}
