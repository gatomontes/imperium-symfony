<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Curia\PlanningCommissionRouter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:curia:deliver-commissions', description: 'Deliver issued planning commissions to sealed Office inboxes')]
final class CuriaDeliverCommissionsCommand extends Command
{
    public function __construct(private readonly PlanningCommissionRouter $router)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('proceeding-id', InputArgument::REQUIRED, 'Exact Curian proceeding identifier')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit complete delivery envelopes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $result = $this->router->deliver((string) $input->getArgument('proceeding-id'));
        } catch (\Throwable $exception) {
            $output->writeln('<error>REFUSED</error> '.$exception->getMessage());

            return self::FAILURE;
        }
        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        $output->writeln('<info>PLANNING_COMMISSIONS_DELIVERED</info>');
        foreach ($result['deliveries'] as $office => $delivery) {
            $output->writeln(sprintf('%s %s %s', ucfirst($office), $delivery['delivery_id'], $delivery['status']));
        }
        $output->writeln('Recipient acceptance: NOT RECORDED');
        $output->writeln('Execution authority: NOT GRANTED');

        return self::SUCCESS;
    }
}
