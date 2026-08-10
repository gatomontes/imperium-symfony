<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\LaCortine\DeterministicBoundaryExecutor;
use App\Imperium\Runtime\LaCortine\DeterministicTransport;
use App\Imperium\Runtime\LaCortine\EnvironmentCredentialBroker;
use App\Imperium\Runtime\LaCortine\IronGate;
use App\Imperium\Runtime\LaCortine\Lazaretto;
use App\Imperium\Runtime\LaCortine\OutboundExecutionMode;
use App\Imperium\Runtime\LaCortine\OutboundRequest;
use App\Imperium\Runtime\LaCortine\TransportResult;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:deterministic:smoke', description: 'Exercise the mechanical La Cortine lane without cognition or network access.')]
final class DeterministicHttpPostSmokeCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $credentialName = 'IMPERIUM_DETERMINISTIC_SMOKE_SECRET';
        $secret = 'smoke-secret-'.bin2hex(random_bytes(12));
        $_ENV[$credentialName] = $secret;
        $payload = json_encode([
            'to' => 'recipient@example.test',
            'artifact_sha256' => hash('sha256', 'prepared-pdf-bytes'),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $now = new \DateTimeImmutable();
        $broker = new EnvironmentCredentialBroker();
        $credential = $broker->issue('env:'.$credentialName, 'commission.deterministic-smoke', 'email.send', $now->modify('+2 minutes'));
        $request = new OutboundRequest(
            'request.deterministic-smoke.'.bin2hex(random_bytes(8)),
            'authorization.deterministic-smoke',
            hash('sha256', 'authorization.deterministic-smoke'),
            'commission.deterministic-smoke',
            'email.send',
            'Send exact prepared artifact to exact recipient',
            OutboundExecutionMode::Deterministic,
            ['recipient@example.test'],
            ['email.send'],
            [$credential->capabilityId],
            hash('sha256', $payload),
            'provider-delivery-receipt/v1',
            $now->modify('+2 minutes'),
        );

        $transport = new class($secret) implements DeterministicTransport {
            public function __construct(private readonly string $expectedSecret) {}
            public function supports(string $operation): bool { return 'email.send' === $operation; }
            public function execute(string $operation, string $destination, string $payload, mixed $authentication): TransportResult
            {
                if ($authentication !== $this->expectedSecret) {
                    throw new \RuntimeException('SMOKE_CREDENTIAL_NOT_BROKERED');
                }
                return new TransportResult(
                    json_encode(['status' => 'accepted', 'provider_id' => 'smoke-message-1'], JSON_THROW_ON_ERROR),
                    ['provider:deterministic-smoke', 'provider-message:smoke-message-1'],
                    new \DateTimeImmutable(),
                );
            }
        };

        try {
            $artifact = (new DeterministicBoundaryExecutor(new IronGate(), $broker, new Lazaretto()))
                ->execute($request, $payload, $credential, $transport, $now);
        } catch (\Throwable $e) {
            $output->writeln('REFUSED '.$e->getMessage());
            return Command::FAILURE;
        } finally {
            unset($_ENV[$credentialName], $_SERVER[$credentialName]);
        }

        if (null !== $artifact->provenance['sortie_id'] || null !== $artifact->provenance['manifestation_id']) {
            $output->writeln('REFUSED DETERMINISTIC_SMOKE_CREATED_SORTIE');
            return Command::FAILURE;
        }
        if (str_contains($artifact->content, $secret) || str_contains(json_encode($artifact->provenance, JSON_THROW_ON_ERROR), $secret)) {
            $output->writeln('REFUSED DETERMINISTIC_SMOKE_LEAKED_CREDENTIAL');
            return Command::FAILURE;
        }

        $output->writeln('ADMITTED');
        $output->writeln('DETERMINISTIC_ROUND_TRIP_OK');
        $output->writeln('operation=email.send');
        $output->writeln('destination=recipient@example.test');
        $output->writeln('capability='.$credential->capabilityId);
        $output->writeln('artifact='.$artifact->artifactId);
        $output->writeln('sortie=NONE');
        $output->writeln('receipt.sha256='.$artifact->contentDigest);

        return Command::SUCCESS;
    }
}
