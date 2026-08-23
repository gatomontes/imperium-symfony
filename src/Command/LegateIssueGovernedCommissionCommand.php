<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Citadel\LegateGovernedCommissionIssuanceService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:legate:issue-governed-commission', description: 'Issue one exact governed commission to an active Legate, pending independent acceptance.')]
final class LegateIssueGovernedCommissionCommand extends Command
{
    public function __construct(private readonly LegateGovernedCommissionIssuanceService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('runtime-activation-id', InputArgument::REQUIRED)
            ->addArgument('issuer-binding-id', InputArgument::REQUIRED)
            ->addArgument('contract-json-file', InputArgument::REQUIRED)
            ->addOption('json', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $path = (string) $input->getArgument('contract-json-file');
            if (!is_file($path)) {
                throw new \InvalidArgumentException('CIT310_GOVERNED_COMMISSION_CONTRACT_FILE_ABSENT');
            }
            $contract = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($contract)) {
                throw new \InvalidArgumentException('CIT307_GOVERNED_COMMISSION_CONTRACT_INVALID');
            }
            $commission = $this->service->issue(
                (string) $input->getArgument('runtime-activation-id'),
                (string) $input->getArgument('issuer-binding-id'),
                $contract,
                new \DateTimeImmutable(),
            );
        } catch (\Throwable $error) {
            $output->writeln('<error>REFUSED</error> '.$error->getMessage());

            return self::FAILURE;
        }
        $output->writeln($input->getOption('json')
            ? json_encode($commission, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            : '<info>GOVERNED_COMMISSION_ISSUED_PENDING_LEGATE_ACCEPTANCE</info> '.$commission['commission_id']);

        return self::SUCCESS;
    }
}
