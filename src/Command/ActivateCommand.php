<?php

declare(strict_types=1);

namespace App\Command;

use App\Bootstrap\Launcher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:activate', description: 'Validate and advance primordial bootstrap through T04')]
final class ActivateCommand extends Command
{
    public function __construct(private readonly Launcher $launcher)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('instance-id', InputArgument::OPTIONAL, 'Immutable instance identifier', 'imperium-local');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $record = $this->launcher->activate((string) $input->getArgument('instance-id'));
        } catch (\Throwable $exception) {
            $output->writeln('<error>REFUSED</error> '.$exception->getMessage());
            return self::FAILURE;
        }
        $output->writeln('<info>'.$record['state'].'</info> generation '.$record['generation']);
        return self::SUCCESS;
    }
}
