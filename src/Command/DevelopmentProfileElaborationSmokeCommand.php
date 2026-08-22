<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Laboratorium\ProfileElaborationSmokeService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'imperium:dev:profile-elaboration-smoke', description: 'Run an isolated governed Profile-elaboration smoke test')]
final class DevelopmentProfileElaborationSmokeCommand extends Command
{
    public function __construct(
        private readonly ProfileElaborationSmokeService $service,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
        #[Autowire('%kernel.environment%')] private readonly string $environment,
    ) { parent::__construct(); }

    protected function configure(): void
    {
        $this->addOption('run-id', null, InputOption::VALUE_REQUIRED, 'Safe unique run identifier')->addOption('json', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ('dev' !== $this->environment) { $output->writeln('<error>REFUSED</error> DEV00_SMOKE_REQUIRES_DEV_ENVIRONMENT'); return self::FAILURE; }
        $runId = $input->getOption('run-id');
        $runId = is_string($runId) && '' !== trim($runId) ? trim($runId) : gmdate('Ymd-His').'-'.bin2hex(random_bytes(3));
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{5,79}$/', $runId)) { $output->writeln('<error>REFUSED</error> DEV04_SMOKE_RUN_ID_INVALID'); return self::FAILURE; }
        $root = $this->projectDir.'/var/imperium-dev/profile-elaboration-smoke/'.$runId;
        try { $result = $this->service->run($root); }
        catch (\Throwable $exception) { $output->writeln('<error>REFUSED</error> '.$exception->getMessage()); return self::FAILURE; }
        if ((bool) $input->getOption('json')) { $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)); return self::SUCCESS; }
        $candidate = $result['candidate'];
        $output->writeln('<info>PROFILE_ELABORATION_SMOKE_SUCCEEDED</info> '.$candidate['candidate_id']);
        $output->writeln('Status: '.$candidate['status']);
        $output->writeln('State root: '.$result['state_root']);
        $output->writeln('Acceptance: '.$result['acceptance']['acceptance_id']);
        $output->writeln('Profile: '.$candidate['profile_id'].' v'.$candidate['profile_version']);
        $output->writeln('Return: '.$result['return']['return_id']);
        $output->writeln('Return status: '.$result['return']['status']);
        $output->writeln('Conscription acceptance: '.$result['return_acceptance']['acceptance_id']);
        $output->writeln('Acceptance status: '.$result['return_acceptance']['status']);
        $output->writeln('Examination assembly request: '.$result['examination_assembly_request']['request_id']);
        $output->writeln('Request status: '.$result['examination_assembly_request']['status']);
        $output->writeln('Senate intake disposition: '.$result['examination_assembly_authorization']['disposition_id']);
        $output->writeln('Authorization status: '.$result['examination_assembly_authorization']['status']);
        if (is_array($result['examination_manifestation'])) $output->writeln('Examination manifestation: '.$result['examination_manifestation']['manifestation']['manifestation_id']);
        return self::SUCCESS;
    }
}
