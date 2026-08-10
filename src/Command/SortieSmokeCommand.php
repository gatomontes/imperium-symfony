<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\LaCortine\OutboundExecutionMode;
use App\Imperium\Runtime\LaCortine\OutboundRequest;
use App\Imperium\Runtime\LaCortine\SortieBoundaryExecutor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:sortie:smoke', description: 'Commission one cognition-only sortie through Iron Gate and admit its return through Lazaretto')]
final class SortieSmokeCommand extends Command
{
    public function __construct(private readonly SortieBoundaryExecutor $executor)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('objective', InputArgument::OPTIONAL, 'Exact one-shot objective', 'Return exactly: SORTIE_ROUND_TRIP_OK');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $objective = trim((string) $input->getArgument('objective'));
        if ('' === $objective) {
            $output->writeln('<error>REFUSED</error> objective is required.');
            return self::FAILURE;
        }

        $now = new \DateTimeImmutable();
        $requestId = 'smoke.'.bin2hex(random_bytes(8));
        $request = new OutboundRequest(
            $requestId,
            'authorization.smoke',
            hash('sha256', 'authorization.smoke'),
            'commission.'.$requestId,
            'ai.cognition',
            $objective,
            OutboundExecutionMode::Sortie,
            ['ai.platform.deepseek'],
            [],
            [],
            hash('sha256', $objective),
            'text/plain; provenance=required',
            $now->modify('+2 minutes'),
        );

        try {
            $artifact = $this->executor->execute($request, $now);
        } catch (\Throwable $exception) {
            $output->writeln('<error>REFUSED</error> '.$exception->getMessage());
            return self::FAILURE;
        }

        $output->writeln('<info>ADMITTED</info>');
        $output->writeln($artifact->content);
        $output->writeln('artifact='.$artifact->artifactId);
        $output->writeln('sortie='.$artifact->provenance['sortie_id']);

        return self::SUCCESS;
    }
}
