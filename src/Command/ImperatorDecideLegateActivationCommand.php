<?php
declare(strict_types=1);
namespace App\Command;

use App\Imperium\Runtime\Imperator\LegateActivationAuthorizationDecisionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:imperator:decide-legate-activation', description: 'Decide activation authorization for one exact bound Legate.')]
final class ImperatorDecideLegateActivationCommand extends Command
{
    public function __construct(private readonly LegateActivationAuthorizationDecisionService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('binding-id', InputArgument::REQUIRED)
            ->addArgument('disposition', InputArgument::REQUIRED)
            ->addArgument('response', InputArgument::REQUIRED)
            ->addArgument('limitations', InputArgument::REQUIRED)
            ->addOption('json', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $decision = $this->service->decide((string) $input->getArgument('binding-id'), (string) $input->getArgument('disposition'), (string) $input->getArgument('response'), (string) $input->getArgument('limitations'), new \DateTimeImmutable());
        } catch (\Throwable $error) {
            $output->writeln('<error>REFUSED</error> '.$error->getMessage());
            return self::FAILURE;
        }
        $output->writeln($input->getOption('json') ? json_encode($decision, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) : '<info>CITADEL_LEGATE_ACTIVATION_DECISION_SEALED</info> '.$decision['decision_id']);
        return self::SUCCESS;
    }
}
