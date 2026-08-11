<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Curia\ImperatorActs;
use App\Imperium\Runtime\Curia\ProceedingStore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:curia:authorize', description: 'Authorize exact declared resources without approving a Mission Plan')]
final class CuriaAuthorizeCommand extends Command
{
    public function __construct(
        private readonly ImperatorActs $acts,
        private readonly ProceedingStore $store,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('proceeding-id', InputArgument::REQUIRED)
            ->addArgument('turn', InputArgument::REQUIRED)
            ->addOption('resource', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Exact declared resource demand; repeat for multiple demands')
            ->addOption('all-demands', null, InputOption::VALUE_NONE, 'Explicitly authorize every resource demand declared by this plan')
            ->addOption('limitations', null, InputOption::VALUE_REQUIRED, 'Binding limitations on the authorization')
            ->addOption('act-id', null, InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $proceedingId = (string) $input->getArgument('proceeding-id');
            $turnSequence = (int) $input->getArgument('turn');
            $resources = $input->getOption('resource');
            if ((bool) $input->getOption('all-demands')) {
                if ([] !== $resources) {
                    throw new \InvalidArgumentException('Choose either --all-demands or explicit --resource values.');
                }
                $turn = $this->store->turn($proceedingId, $turnSequence);
                $resources = is_array($turn) ? ($turn['resource_demands'] ?? []) : [];
            }
            $act = $this->acts->authorizeResources(
                $proceedingId,
                $turnSequence,
                is_array($resources) ? $resources : [],
                is_string($input->getOption('limitations')) ? $input->getOption('limitations') : null,
                is_string($input->getOption('act-id')) ? $input->getOption('act-id') : null,
            );
        } catch (\Throwable $exception) {
            $output->writeln('<error>REFUSED</error> '.$exception->getMessage());

            return self::FAILURE;
        }
        $output->writeln('<info>RESOURCES_AUTHORIZED</info> '.$act['act_id']);
        foreach ($act['resources'] as $resource) {
            $output->writeln('- '.$resource);
        }
        $output->writeln('Plan approval: NOT IMPLIED');
        $output->writeln('Commissioning ready: '.($act['readiness']['commissioning_ready'] ? 'YES' : 'NO'));
        $output->writeln('Execution authority: NOT GRANTED');

        return self::SUCCESS;
    }
}
