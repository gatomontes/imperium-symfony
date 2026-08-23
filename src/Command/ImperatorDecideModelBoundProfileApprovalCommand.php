<?php
declare(strict_types=1);
namespace App\Command;

use App\Imperium\Runtime\Imperator\ModelBoundProfileApprovalDecisionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:imperator:decide-model-bound-profile-approval', description: 'Seal the Imperator decision for one exact model-bound Senate Profile disposition.')]
final class ImperatorDecideModelBoundProfileApprovalCommand extends Command
{
    public function __construct(private readonly ModelBoundProfileApprovalDecisionService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('senate-disposition-id', InputArgument::REQUIRED)
            ->addArgument('disposition', InputArgument::REQUIRED)
            ->addArgument('response', InputArgument::REQUIRED)
            ->addArgument('limitations', InputArgument::REQUIRED)
            ->addOption('json', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $decision = $this->service->decide(
                (string) $input->getArgument('senate-disposition-id'),
                (string) $input->getArgument('disposition'),
                (string) $input->getArgument('response'),
                (string) $input->getArgument('limitations'),
            );
        } catch (\Throwable $error) {
            $output->writeln('<error>REFUSED</error> '.$error->getMessage());
            return self::FAILURE;
        }
        $output->writeln($input->getOption('json') ? json_encode($decision, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) : '<info>MODEL_BOUND_IMPERATOR_PROFILE_DECISION_SEALED</info> '.$decision['decision_id']);
        return self::SUCCESS;
    }
}
