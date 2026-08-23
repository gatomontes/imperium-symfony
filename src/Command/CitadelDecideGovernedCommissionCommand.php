<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Citadel\CitadelGovernedCommissionDispositionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:citadel:decide-governed-commission', description: 'Accept or refuse one exact governed commission as its target Citadel Officer.')]
final class CitadelDecideGovernedCommissionCommand extends Command
{
    public function __construct(private readonly CitadelGovernedCommissionDispositionService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('commission-id', InputArgument::REQUIRED)
            ->addArgument('target-binding-id', InputArgument::REQUIRED)
            ->addArgument('disposition', InputArgument::REQUIRED, 'ACCEPTED or REFUSED')
            ->addArgument('rationale', InputArgument::REQUIRED)
            ->addOption('json', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $decision = $this->service->decide(
                (string) $input->getArgument('commission-id'),
                (string) $input->getArgument('target-binding-id'),
                strtoupper((string) $input->getArgument('disposition')),
                (string) $input->getArgument('rationale'),
                new \DateTimeImmutable(),
            );
        } catch (\Throwable $error) {
            $output->writeln('<error>REFUSED</error> '.$error->getMessage());

            return self::FAILURE;
        }
        $output->writeln($input->getOption('json')
            ? json_encode($decision, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            : '<info>'.$decision['status'].'</info> '.$decision['disposition_id']);

        return self::SUCCESS;
    }
}
