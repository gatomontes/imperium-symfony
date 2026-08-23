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

#[AsCommand(name: 'imperium:sortie:http-get-smoke', description: 'Commission one governed HTTP GET sortie and admit raw evidence plus interpretation')]
final class SortieHttpGetSmokeCommand extends Command
{
    public function __construct(private readonly SortieBoundaryExecutor $executor)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('url', InputArgument::OPTIONAL, 'Exact HTTPS destination', 'https://example.com')
            ->addArgument('objective', InputArgument::OPTIONAL, 'Interpretation objective', 'State the page title from the raw evidence and nothing else.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $url = trim((string) $input->getArgument('url'));
        $objective = trim((string) $input->getArgument('objective'));
        if ('' === $url || '' === $objective) {
            $output->writeln('<error>REFUSED</error> url and objective are required.');
            return self::FAILURE;
        }

        $now = new \DateTimeImmutable();
        $requestId = 'tool-smoke.'.bin2hex(random_bytes(8));
        $capabilityId = 'capability.http-get.'.bin2hex(random_bytes(8));
        $request = new OutboundRequest(
            $requestId,
            'authorization.tool-smoke',
            hash('sha256', 'authorization.tool-smoke'),
            'commission.'.$requestId,
            'external.observe',
            $objective,
            OutboundExecutionMode::Sortie,
            [$url],
            ['http.get'],
            [$capabilityId],
            hash('sha256', $objective.'|'.$url),
            'application/json; evidence+interpretation; provenance=required',
            $now->modify('+2 minutes'),
        );

        try {
            $artifact = $this->executor->execute($request, $now);
        } catch (\Throwable $exception) {
            $output->writeln('<error>REFUSED</error> '.$exception->getMessage());
            return self::FAILURE;
        }

        $decoded = json_decode($artifact->content, true);
        $output->writeln('<info>ADMITTED</info>');
        if (is_array($decoded) && isset($decoded['evidence'], $decoded['interpretation'])) {
            $output->writeln('evidence.sha256='.$decoded['evidence']['sha256']);
            $output->writeln('evidence.source='.$decoded['evidence']['source_id']);
            $output->writeln('interpretation='.$decoded['interpretation']);
        } else {
            $output->writeln($artifact->content);
        }
        $output->writeln('tool=http.get');
        $output->writeln('capability='.$capabilityId);
        $output->writeln('artifact='.$artifact->artifactId);
        $output->writeln('sortie='.$artifact->provenance['sortie_id']);

        return self::SUCCESS;
    }
}
