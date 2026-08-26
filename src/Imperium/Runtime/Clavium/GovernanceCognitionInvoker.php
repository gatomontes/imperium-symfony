<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\DeepSeekDelegateModelConfiguration;
use App\Imperium\Runtime\Citadel\DeepSeekDelegatePlatformAdapter;
use App\Imperium\Runtime\Clock;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

final readonly class GovernanceCognitionInvoker
{
    public function __construct(private GovernanceClaimBoundCredentialBroker $broker, private ProviderInvocationJournalService $journal, private ProviderResponseEnvelopeService $responses, private DeepSeekDelegatePlatformAdapter $platform, private Clock $clock, private ?DeepSeekDelegateModelConfiguration $configuration = null) {}

    public function invoke(string $cluster, string $type, string $authorityId, string $seat, string $purpose, array $inputs, string $prompt): string
    {
        foreach ([$cluster, $type, $seat, $purpose] as $scope) {
            if (!preg_match('/^[a-z0-9][a-z0-9._-]{2,127}$/', $scope)) { throw new \InvalidArgumentException('GCA452_GOVERNANCE_COGNITION_STAGE_INVALID'); }
        }
        $claim = $this->broker->claimFor($cluster, $type, $authorityId, $seat, $purpose, hash('sha256', CanonicalJson::encode($inputs)), $this->clock->now());
        try { $configuration = ($this->configuration ?? new DeepSeekDelegateModelConfiguration())->normalize(DeepSeekDelegatePlatformAdapter::RUNTIME_MODEL, $claim['model_configuration'] ?? null); }
        catch (\Throwable) { $this->journal->markPreIoFailure($claim, 'GOVERNANCE_CONFIGURATION_INVALID', $this->clock->now()); throw new \RuntimeException('GCA453_GOVERNANCE_PROVIDER_PRE_IO_FAILURE'); }
        try { $this->journal->reserveGovernance($claim, $this->clock->now()); }
        catch (\Throwable) { throw new \RuntimeException('GCA454_GOVERNANCE_PROVIDER_REPLAY_PROHIBITED'); }
        $started = false;
        try {
            return $this->broker->consume($claim, $this->clock->now(), function (mixed $secret) use ($claim, $configuration, $prompt, &$started): string {
                if (!is_string($secret) || '' === $secret) { throw new \RuntimeException('GCA455_GOVERNANCE_PROVIDER_CREDENTIAL_UNAVAILABLE'); }
                $this->journal->startReservedGovernance($claim, $this->clock->now()); $started = true;
                try { $text = $this->platform->invoke($secret, DeepSeekDelegatePlatformAdapter::RUNTIME_MODEL, new MessageBag(Message::ofUser($prompt)), $configuration, $claim['provider_request']['idempotency_identity']); }
                catch (\Throwable) { $this->journal->markUnknown($claim, $this->clock->now()); throw new \RuntimeException('GCA456_GOVERNANCE_PROVIDER_OUTCOME_UNKNOWN'); }
                $at = $this->clock->now(); $this->responses->seal($claim, $text, $at); $this->journal->sealResponse($claim, $text, $at); return $text;
            });
        } catch (\Throwable $exception) {
            if (!$started) { $this->journal->failReservedGovernance($claim, 'GOVERNANCE_CREDENTIAL_RESOLUTION_FAILED', $this->clock->now()); throw new \RuntimeException('GCA453_GOVERNANCE_PROVIDER_PRE_IO_FAILURE', 0, $exception); }
            throw $exception;
        }
    }
}
