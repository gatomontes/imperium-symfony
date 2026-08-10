<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\LaCortine\BearerJsonPostTransport;
use App\Imperium\Runtime\LaCortine\DeterministicBoundaryExecutor;
use App\Imperium\Runtime\LaCortine\EnvironmentCredentialBroker;
use App\Imperium\Runtime\LaCortine\IronGate;
use App\Imperium\Runtime\LaCortine\Lazaretto;
use App\Imperium\Runtime\LaCortine\OutboundExecutionMode;
use App\Imperium\Runtime\LaCortine\OutboundRequest;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:deterministic:http-post-smoke', description: 'Exercise the mechanical La Cortine lane without cognition.')]
final class DeterministicHttpPostSmokeCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('destination', null, InputOption::VALUE_REQUIRED, 'Exact HTTPS echo endpoint', 'https://httpbin.org/post')
            ->addOption('credential-env', null, InputOption::VALUE_REQUIRED, 'Environment variable holding the bearer test credential', 'IMPERIUM_SMOKE_TOKEN');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $destination = (string) $input->getOption('destination');
        $credentialEnv = (string) $input->getOption('credential-env');
        $payload = json_encode([
            'kind' => 'imperium-deterministic-smoke',
            'nonce' => bin2hex(random_bytes(8)),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $now = new \DateTimeImmutable();
        $broker = new EnvironmentCredentialBroker();
        $credential = $broker->issue('env:'.$credentialEnv, 'commission.deterministic-smoke', 'http.post.json', $now->modify('+2 minutes'));
        $request = new OutboundRequest(
            'request.deterministic-smoke.'.bin2hex(random_bytes(8)),
            'authorization.deterministic-smoke',
            hash('sha256', 'authorization.deterministic-smoke'),
            'commission.deterministic-smoke',
            'http.post.json',
            'Prove exact mechanical external execution without cognitive agency',
            OutboundExecutionMode::Deterministic,
            [$destination],
            ['http.post'],
            [$credential->capabilityId],
            hash('sha256', $payload),
            'http-provider-receipt/v1',
            $now->modify('+2 minutes'),
        );

        try {
            $artifact = (new DeterministicBoundaryExecutor(new IronGate(), $broker, new Lazaretto()))
                ->execute($request, $payload, $credential, new BearerJsonPostTransport(), $now);
        } catch (\Throwable $e) {
            $output->writeln('REFUSED '.$e->getMessage());
            return Command::FAILURE;
        }

        $output->writeln('ADMITTED');
        $output->writeln('DETERMINISTIC_ROUND_TRIP_OK');
        $output->writeln('operation=http.post.json');
        $output->writeln('destination='.$destination);
        $output->writeln('capability='.$credential->capabilityId);
        $output->writeln('artifact='.$artifact->artifactId);
        $output->writeln('sortie='.($artifact->provenance['sortie_id'] ?? 'NONE'));
        $output->writeln('receipt.sha256='.$artifact->contentDigest);

        return Command::SUCCESS;
    }
}
