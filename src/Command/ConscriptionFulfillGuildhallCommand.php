<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Conscription\GuildhallConscriptionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:conscription:fulfill-guildhall', description: 'Instantiate and qualify the four summoned canonical Guildhall Legates')]
final class ConscriptionFulfillGuildhallCommand extends Command
{
    public function __construct(private readonly GuildhallConscriptionService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('summons-id', InputArgument::REQUIRED, 'Exact validated Guildhall summons identifier')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit complete qualified manifestation packets');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $result = $this->service->fulfill((string) $input->getArgument('summons-id'));
        } catch (\Throwable $exception) {
            $output->writeln('<error>REFUSED</error> '.$exception->getMessage());

            return self::FAILURE;
        }
        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }
        $output->writeln('<info>GUILDHALL_MANIFESTATIONS_QUALIFIED</info> '.$result['summons_id']);
        $output->writeln('Recruiter: '.$result['recruiter']['manifestation_id']);
        foreach ($result['deliveries'] as $delivery) {
            $output->writeln('- '.$delivery['candidate']['target_seat'].' '.$delivery['candidate']['status']);
        }
        $output->writeln('Seat binding authority: NOT GRANTED');
        $output->writeln('Recipient acceptance: NOT RECORDED');
        $output->writeln('Execution authority: NOT GRANTED');

        return self::SUCCESS;
    }
}
