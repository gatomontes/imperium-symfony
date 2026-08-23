<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Citadel\LegateCognitionTurnAuthorizationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:legate:authorize-cognition-turn', description: 'Decide one bounded cognition-turn authorization for an accepted Legate commission.')]
final class LegateAuthorizeCognitionTurnCommand extends Command
{
    public function __construct(private readonly LegateCognitionTurnAuthorizationService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('commission-disposition-id', InputArgument::REQUIRED)
            ->addArgument('issuer-binding-id', InputArgument::REQUIRED)
            ->addArgument('decision', InputArgument::REQUIRED, 'AUTHORIZED, REFUSED, DEFERRED, or REVOKED')
            ->addArgument('rationale', InputArgument::REQUIRED)
            ->addArgument('expires-at', InputArgument::REQUIRED, 'ISO-8601 instant, no more than 15 minutes ahead')
            ->addOption('json', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $result = $this->service->decide(
                (string) $input->getArgument('commission-disposition-id'),
                (string) $input->getArgument('issuer-binding-id'),
                strtoupper((string) $input->getArgument('decision')),
                (string) $input->getArgument('rationale'),
                new \DateTimeImmutable((string) $input->getArgument('expires-at')),
                new \DateTimeImmutable(),
            );
        } catch (\Throwable $error) {
            $output->writeln('<error>REFUSED</error> '.$error->getMessage());

            return self::FAILURE;
        }
        $output->writeln($input->getOption('json') ? json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) : '<info>'.$result['status'].'</info> '.$result['decision_id']);

        return self::SUCCESS;
    }
}
