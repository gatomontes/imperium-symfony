<?php
declare(strict_types=1);
namespace App\Command;

use App\Imperium\Runtime\Conscription\LegateRuntimeActivationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:conscription:activate-legate-runtime', description: 'Mechanically activate one exactly authorized bound Legate runtime.')]
final class ConscriptionActivateLegateRuntimeCommand extends Command
{
    public function __construct(private readonly LegateRuntimeActivationService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('activation-authorization-decision-id', InputArgument::REQUIRED)->addOption('json', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $activation = $this->service->activate((string) $input->getArgument('activation-authorization-decision-id'), new \DateTimeImmutable());
        } catch (\Throwable $error) {
            $output->writeln('<error>REFUSED</error> '.$error->getMessage());
            return self::FAILURE;
        }
        $output->writeln($input->getOption('json') ? json_encode($activation, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) : '<info>CITADEL_LEGATE_RUNTIME_ACTIVE</info> '.$activation['activation_id']);
        return self::SUCCESS;
    }
}
