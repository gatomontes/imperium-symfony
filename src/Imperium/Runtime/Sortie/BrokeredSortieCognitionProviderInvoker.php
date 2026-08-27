<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Sortie;

use App\Imperium\Runtime\Citadel\DeepSeekDelegatePlatformAdapter;
use App\Imperium\Runtime\Citadel\DeepSeekDelegateModelConfiguration;
use App\Imperium\Runtime\Clock;
use App\Imperium\Runtime\LaCortine\CredentialBroker;
use App\Imperium\Runtime\Persistence\MutableStateStore;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

final readonly class BrokeredSortieCognitionProviderInvoker implements SortieCognitionProviderInvoker
{
    private const JOURNAL = 'var/imperium/runtime/sortie-cognition-invocations';

    public function __construct(
        private CredentialBroker $broker,
        private MutableStateStore $state,
        private DeepSeekDelegatePlatformAdapter $platform,
        private DeepSeekDelegateModelConfiguration $configuration,
        private Clock $clock,
    ) {
    }

    public function invoke(SortieCognitionAuthority $authority, string $prompt): string
    {
        $now = $this->clock->now();
        if ($now >= $authority->expiresAt) {
            throw new \RuntimeException('SORTIE_COGNITION_AUTHORITY_EXPIRED');
        }

        $path = self::JOURNAL.'/'.$authority->digest.'.json';
        try {
            $record = $this->state->compareAndSwap($path, null, [
                'schema' => 'imperium.sortie-cognition-provider-invocation/v1',
                'authority' => ['type' => 'la-cortine.sortie-cognition/v1', 'digest' => $authority->digest],
                'execution_id' => $authority->executionId,
                'authorization_id' => $authority->authorizationId,
                'external_io_started' => false,
                'status' => 'INVOCATION_RESERVED_PRE_IO',
                'automatic_replay_permitted' => false,
                'reserved_at' => $now->format(DATE_ATOM),
            ]);
        } catch (\RuntimeException $exception) {
            throw new \RuntimeException('SORTIE_COGNITION_REPLAY_PROHIBITED', 0, $exception);
        }

        $capability = $this->broker->issue(
            DeepSeekDelegatePlatformAdapter::CREDENTIAL_REFERENCE,
            $authority->commissionId,
            'sortie.'.DeepSeekDelegatePlatformAdapter::OPERATION,
            $authority->expiresAt,
            1,
        );
        $started = false;

        try {
            return $this->broker->consume($capability, function (mixed $secret) use ($authority, $prompt, $path, $record, &$started): string {
                if (!is_string($secret) || '' === $secret) {
                    throw new \RuntimeException('SORTIE_COGNITION_CREDENTIAL_UNAVAILABLE');
                }
                $record = $this->transition($path, $record, 'INVOCATION_RESERVED_PRE_IO', [
                    'external_io_started' => true,
                    'status' => 'INVOCATION_IN_FLIGHT',
                    'started_at' => $this->clock->now()->format(DATE_ATOM),
                ]);
                $started = true;

                try {
                    $text = $this->platform->invoke(
                        $secret,
                        DeepSeekDelegatePlatformAdapter::RUNTIME_MODEL,
                        new MessageBag(Message::ofUser($prompt)),
                        $this->configuration->normalize(DeepSeekDelegatePlatformAdapter::RUNTIME_MODEL, ['temperature' => 0.2]),
                        'sortie:'.$authority->digest,
                    );
                } catch (\Throwable $exception) {
                    $this->transition($path, $record, 'INVOCATION_IN_FLIGHT', [
                        'status' => 'PROVIDER_OUTCOME_UNKNOWN_REPLAY_PROHIBITED',
                        'resolved_at' => $this->clock->now()->format(DATE_ATOM),
                    ]);
                    throw new \RuntimeException('SORTIE_COGNITION_PROVIDER_OUTCOME_UNKNOWN', 0, $exception);
                }

                $this->transition($path, $record, 'INVOCATION_IN_FLIGHT', [
                    'status' => 'PROVIDER_RESPONSE_SEALED',
                    'provider_response_identity' => 'sha256:'.hash('sha256', $text),
                    'resolved_at' => $this->clock->now()->format(DATE_ATOM),
                ]);

                return $text;
            });
        } catch (\Throwable $exception) {
            if (!$started) {
                $this->transition($path, $record, 'INVOCATION_RESERVED_PRE_IO', [
                    'status' => 'INVOCATION_FAILED_PRE_IO_REPLAY_PROHIBITED',
                    'resolved_at' => $this->clock->now()->format(DATE_ATOM),
                ]);
                throw new \RuntimeException('SORTIE_COGNITION_PRE_IO_FAILURE', 0, $exception);
            }
            throw $exception;
        }
    }

    private function transition(string $path, array $record, string $expectedStatus, array $changes): array
    {
        if ($expectedStatus !== ($record['status'] ?? null)) {
            throw new \RuntimeException('SORTIE_COGNITION_JOURNAL_TRANSITION_INVALID');
        }
        $digest = $record['record_digest'] ?? null;
        if (!is_string($digest)) {
            throw new \RuntimeException('SORTIE_COGNITION_JOURNAL_TRANSITION_INVALID');
        }
        unset($record['record_digest']);

        return $this->state->compareAndSwap($path, $digest, array_replace($record, $changes));
    }
}
