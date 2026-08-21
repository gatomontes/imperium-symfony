<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\Curia\PersonnelUseAuthorizationDecisionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:curia:decide-personnel-use', description: 'Record one exact Imperator personnel-use disposition')]
final class CuriaDecidePersonnelUseAuthorizationCommand extends Command
{
    public function __construct(private readonly PersonnelUseAuthorizationDecisionService $service) { parent::__construct(); }

    protected function configure(): void
    {
        $this->addArgument('request-id', InputArgument::REQUIRED)
            ->addArgument('disposition', InputArgument::REQUIRED, 'AUTHORIZED, REFUSED, RETURNED_FOR_REVISION, ALTERNATIVE_PROPOSED, CLARIFICATION_REQUIRED, or DEFERRED')
            ->addArgument('response', InputArgument::REQUIRED, 'Exact Imperator rationale, question, objection, or proposal')
            ->addOption('limitations', null, InputOption::VALUE_REQUIRED)
            ->addOption('json', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $act = $this->service->decide(
                (string) $input->getArgument('request-id'),
                (string) $input->getArgument('disposition'),
                (string) $input->getArgument('response'),
                is_string($input->getOption('limitations')) ? $input->getOption('limitations') : null,
            );
        } catch (\Throwable $exception) {
            $output->writeln('<error>REFUSED</error> '.$exception->getMessage());
            return self::FAILURE;
        }
        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($act, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            return self::SUCCESS;
        }
        $output->writeln('<info>PERSONNEL_USE_DECISION_RECORDED</info> '.$act['act_id']);
        $output->writeln('Disposition: '.$act['disposition']);
        $output->writeln('Personnel-use authority: '.($act['personnel_use_authority'] ? 'GRANTED FOR EXACT CAPABILITY COMMITMENTS' : 'NOT GRANTED'));
        $output->writeln('Reservation authority: NOT GRANTED');
        $output->writeln('Execution authority: NOT GRANTED');
        return self::SUCCESS;
    }
}
