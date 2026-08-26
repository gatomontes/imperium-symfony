<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Mission;

use App\Imperium\Runtime\Citadel\DeepSeekDelegateModelConfiguration;
use App\Imperium\Runtime\Citadel\DeepSeekDelegatePlatformAdapter;
use App\Imperium\Runtime\Clavium\OperationalClaimBoundCredentialBroker;
use App\Imperium\Runtime\Clavium\ProviderInvocationJournalService;
use App\Imperium\Runtime\Clavium\ProviderResponseEnvelopeService;
use App\Imperium\Runtime\Clock;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

final readonly class SymfonyAiOperationalExecutionCognitionGateway implements OperationalExecutionCognitionGateway
{
    public function __construct(
        private OperationalClaimBoundCredentialBroker $credentialBroker,
        private ProviderInvocationJournalService $journal,
        private ProviderResponseEnvelopeService $responses,
        private DeepSeekDelegatePlatformAdapter $platform,
        private Clock $clock,
        private ?DeepSeekDelegateModelConfiguration $configuration = null,
    ) {
    }

    public function execute(array $authorization, array $manifestation): array
    {
        $claim = $this->credentialBroker->claimFor($authorization, $manifestation, $this->clock->now());
        try {
            $configuration = ($this->configuration ?? new DeepSeekDelegateModelConfiguration())->normalize(
                DeepSeekDelegatePlatformAdapter::RUNTIME_MODEL,
                $claim['model_configuration'] ?? null,
            );
        } catch (\Throwable) {
            $this->journal->markPreIoFailure($claim, 'OPERATIONAL_CONFIGURATION_INVALID', $this->clock->now());
            throw new \RuntimeException('M213_OPERATIONAL_PROVIDER_PRE_IO_FAILURE');
        }
        try {
            $this->journal->reserveOperational($claim, $this->clock->now());
        } catch (\Throwable) {
            throw new \RuntimeException('M214_OPERATIONAL_PROVIDER_REPLAY_PROHIBITED');
        }

        $providerOperationStarted = false;
        try {
            $response = $this->credentialBroker->consume(
                $claim,
                $this->clock->now(),
                function (mixed $secret) use ($authorization, $manifestation, $claim, $configuration, &$providerOperationStarted): string {
                    if (!is_string($secret) || '' === $secret) {
                        throw new \RuntimeException('M211_OPERATIONAL_PROVIDER_CREDENTIAL_UNAVAILABLE');
                    }

                    $this->journal->startReservedOperational($claim, $this->clock->now());
                    $providerOperationStarted = true;
                    try {
                        $text = $this->platform->invoke(
                            $secret,
                            DeepSeekDelegatePlatformAdapter::RUNTIME_MODEL,
                            new MessageBag(Message::ofUser($this->prompt($authorization, $manifestation))),
                            $configuration,
                            $claim['provider_request']['idempotency_identity'],
                        );
                    } catch (\Throwable) {
                        $this->journal->markUnknown($claim, $this->clock->now());
                        throw new \RuntimeException('M212_OPERATIONAL_PROVIDER_OUTCOME_UNKNOWN');
                    }

                    $sealedAt = $this->clock->now();
                    $this->responses->seal($claim, $text, $sealedAt);
                    $this->journal->sealResponse($claim, $text, $sealedAt);

                    return $text;
                },
            );
        } catch (\Throwable $exception) {
            if (!$providerOperationStarted) {
                $this->journal->failReservedOperational($claim, 'OPERATIONAL_CREDENTIAL_RESOLUTION_FAILED', $this->clock->now());
                throw new \RuntimeException('M213_OPERATIONAL_PROVIDER_PRE_IO_FAILURE');
            }

            throw $exception;
        }

        return $this->parse($response);
    }

    private function prompt(array $authorization, array $manifestation): string
    {
        return implode("\n", [
            'You are the exact operational Manifestation bound to one mission Seat. Perform only the single internal cognition iteration explicitly authorized in this message.',
            'Exact one-use execution authorization: '.json_encode($authorization, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'Exact bound Manifestation: '.json_encode($manifestation, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'Use only the supplied input, Persona, installed Profile, generic Officer substrate, and sealed lineage. Do not use tools, memory, or undeclared data. Produce one attributable bounded output, evaluate the supplied stop conditions, and stop.',
            'Return one JSON object with exactly disposition, output, evidence_claims, uncertainties, stop_condition_triggered, and stop_rationale. disposition must be COMPLETED or STOPPED. output and stop_rationale must be non-empty strings; evidence_claims and uncertainties must be arrays of non-empty strings; stop_condition_triggered must be boolean. No markdown or extra fields.',
        ]);
    }

    private function parse(string $content): array
    {
        $content = trim($content);
        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $content) ?? $content;
        }
        try {
            $result = json_decode(trim($content), true, 16, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException('M209_OPERATIONAL_EXECUTION_COGNITION_INVALID: JSON_INVALID', 0, $exception);
        }

        $keys = is_array($result) ? array_keys($result) : [];
        sort($keys, SORT_STRING);
        if (['disposition', 'evidence_claims', 'output', 'stop_condition_triggered', 'stop_rationale', 'uncertainties'] !== $keys
            || !in_array($result['disposition'] ?? null, ['COMPLETED', 'STOPPED'], true)
            || !is_string($result['output'] ?? null) || '' === trim($result['output'])
            || !is_bool($result['stop_condition_triggered'] ?? null)
            || ('STOPPED' === $result['disposition']) !== $result['stop_condition_triggered']
            || !is_string($result['stop_rationale'] ?? null) || '' === trim($result['stop_rationale'])) {
            throw new \RuntimeException('M209_OPERATIONAL_EXECUTION_COGNITION_INVALID: CONTRACT_INVALID');
        }
        foreach (['evidence_claims', 'uncertainties'] as $field) {
            if (!is_array($result[$field]) || !array_is_list($result[$field])) {
                throw new \RuntimeException('M209_OPERATIONAL_EXECUTION_COGNITION_INVALID: CONTRACT_INVALID');
            }
            foreach ($result[$field] as $value) {
                if (!is_string($value) || '' === trim($value)) {
                    throw new \RuntimeException('M209_OPERATIONAL_EXECUTION_COGNITION_INVALID: CONTRACT_INVALID');
                }
            }
        }

        return $result;
    }
}
