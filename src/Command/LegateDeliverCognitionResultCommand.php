<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Citadel\LegateCognitionResultDeliveryService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:legate:deliver-cognition-result', description: 'Deliver one exact sealed Legate cognition result to its original commissioner for review.')]
final class LegateDeliverCognitionResultCommand extends Command
{
    public function __construct(private readonly LegateCognitionResultDeliveryService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('turn-id', InputArgument::REQUIRED)
            ->addArgument('commissioner-binding-id', InputArgument::REQUIRED)
            ->addOption('json', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $delivery = $this->service->deliver(
                (string) $input->getArgument('turn-id'),
                (string) $input->getArgument('commissioner-binding-id'),
                new \DateTimeImmutable(),
            );
        } catch (\Throwable $error) {
            $output->writeln('<error>REFUSED</error> '.$error->getMessage());

            return self::FAILURE;
        }
        $output->writeln($input->getOption('json')
            ? json_encode($delivery, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            : '<info>'.$delivery['status'].'</info> '.$delivery['delivery_id']);

        return self::SUCCESS;
    }
}
