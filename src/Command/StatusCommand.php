<?php

declare(strict_types=1);

namespace App\Command;

use App\Bootstrap\StateStore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:status', description: 'Report the durable Imperium bootstrap checkpoint')]
final class StatusCommand extends Command
{
    public function __construct(private readonly StateStore $store)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $record = $this->store->read();
        if (null === $record) {
            $output->writeln('UNINITIALIZED');
            return self::SUCCESS;
        }
        $output->writeln($record['state'].' generation '.$record['generation']);
        $output->writeln('instance '.$record['binding']['instance_id']);
        $output->writeln('manifest '.$record['binding']['manifest_id']);
        return self::SUCCESS;
    }
}
