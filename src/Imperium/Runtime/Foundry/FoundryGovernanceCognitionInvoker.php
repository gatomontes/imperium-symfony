<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\DeepSeekDelegateModelConfiguration;
use App\Imperium\Runtime\Citadel\DeepSeekDelegatePlatformAdapter;
use App\Imperium\Runtime\Clavium\GovernanceClaimBoundCredentialBroker;
use App\Imperium\Runtime\Clavium\ProviderInvocationJournalService;
use App\Imperium\Runtime\Clavium\ProviderResponseEnvelopeService;
use App\Imperium\Runtime\Clock;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

final readonly class FoundryGovernanceCognitionInvoker
{
    public function __construct(private GovernanceClaimBoundCredentialBroker $broker, private ProviderInvocationJournalService $journal, private ProviderResponseEnvelopeService $responses, private DeepSeekDelegatePlatformAdapter $platform, private Clock $clock, private ?DeepSeekDelegateModelConfiguration $configuration = null) {}

    public function invoke(string $type, string $authorityId, string $seat, array $inputs, string $prompt): string
    {
        $purpose = match ($type) {
            'persona-specification' => 'specify-persona',
            'persona-specification-revision' => 'revise-persona-specification',
            'persona-review' => 'review-persona',
            'adversarial-persona-review' => 'adversarial-review-persona',
            default => throw new \InvalidArgumentException('GCF174_FOUNDRY_COGNITION_STAGE_INVALID'),
        };
        $claim = $this->broker->claimFor('foundry', $type, $authorityId, $seat, $purpose, hash('sha256', CanonicalJson::encode($inputs)), $this->clock->now());
        try { $configuration = ($this->configuration ?? new DeepSeekDelegateModelConfiguration())->normalize(DeepSeekDelegatePlatformAdapter::RUNTIME_MODEL, $claim['model_configuration'] ?? null); }
        catch (\Throwable) { $this->journal->markPreIoFailure($claim, 'FOUNDRY_CONFIGURATION_INVALID', $this->clock->now()); throw new \RuntimeException('GCF175_FOUNDRY_PROVIDER_PRE_IO_FAILURE'); }
        try { $this->journal->reserveGovernance($claim, $this->clock->now()); }
        catch (\Throwable) { throw new \RuntimeException('GCF176_FOUNDRY_PROVIDER_REPLAY_PROHIBITED'); }
        $started = false;
        try {
            return $this->broker->consume($claim, $this->clock->now(), function (mixed $secret) use ($claim, $configuration, $prompt, &$started): string {
                if (!is_string($secret) || '' === $secret) { throw new \RuntimeException('GCF177_FOUNDRY_PROVIDER_CREDENTIAL_UNAVAILABLE'); }
                $this->journal->startReservedGovernance($claim, $this->clock->now()); $started = true;
                try { $text = $this->platform->invoke($secret, DeepSeekDelegatePlatformAdapter::RUNTIME_MODEL, new MessageBag(Message::ofUser($prompt)), $configuration, $claim['provider_request']['idempotency_identity']); }
                catch (\Throwable) { $this->journal->markUnknown($claim, $this->clock->now()); throw new \RuntimeException('GCF178_FOUNDRY_PROVIDER_OUTCOME_UNKNOWN'); }
                $at = $this->clock->now(); $this->responses->seal($claim, $text, $at); $this->journal->sealResponse($claim, $text, $at); return $text;
            });
        } catch (\Throwable $exception) {
            if (!$started) { $this->journal->failReservedGovernance($claim, 'FOUNDRY_CREDENTIAL_RESOLUTION_FAILED', $this->clock->now()); throw new \RuntimeException('GCF175_FOUNDRY_PROVIDER_PRE_IO_FAILURE', 0, $exception); }
            throw $exception;
        }
    }
}
