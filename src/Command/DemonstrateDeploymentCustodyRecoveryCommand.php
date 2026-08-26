<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Evidence\DeploymentCustodyCrashDemonstration;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:demonstrate:deployment-custody-recovery', description: 'Run Crash Demonstration 2 and retain private evidence plus a sanitized summary')]
final class DemonstrateDeploymentCustodyRecoveryCommand extends Command
{
    public function __construct(private readonly DeploymentCustodyCrashDemonstration $demonstration) { parent::__construct(); }

    protected function configure(): void
    {
        $this->addOption('evidence-dir', null, InputOption::VALUE_REQUIRED, 'Private local evidence destination; never commit its contents', 'var/imperium/private-evidence/crash-demonstration-2');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $result = $this->demonstration->run((string) $input->getOption('evidence-dir'));
        } catch (\Throwable $error) {
            $output->writeln('<error>REFUSED</error> '.$error->getMessage());
            return self::FAILURE;
        }
        $output->writeln('<info>PROVED</info> Crash Demonstration 2: deployment custody recovery');
        $output->writeln('Cases: '.$result['summary']['cases_executed']);
        $output->writeln('Private evidence: '.$result['private_evidence_file']);
        $output->writeln('Sanitized summary: '.$result['sanitized_summary_file']);
        $output->writeln('Runtime activation created: false');
        return self::SUCCESS;
    }
}
