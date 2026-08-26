<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel;

use App\Imperium\Runtime\Clavium\ProviderInvocationJournalService;
use App\Imperium\Runtime\Clavium\ProviderResponseEnvelopeService;
use App\Imperium\Runtime\Clavium\ClaimBoundCredentialBroker;
use App\Imperium\Runtime\Clock;
use Symfony\AI\Platform\Message\MessageBag;

final readonly class SymfonyAiBrokeredDelegateProviderInvoker implements DelegateProviderInvoker
{
    public function __construct(
        private ClaimBoundCredentialBroker $credentialBroker,
        private ProviderInvocationJournalService $journal,
        private ProviderResponseEnvelopeService $responses,
        private DeepSeekDelegatePlatformAdapter $platform,
        private Clock $clock,
        private ?DeepSeekDelegateModelConfiguration $configuration = null,
    ) {
    }

    public function invoke(
        array $claim,
        string $runtimeModel,
        MessageBag $messages,
        array $configuration,
    ): string {
        $this->assertClaimScope($claim, $runtimeModel);
        $configuration = ($this->configuration ?? new DeepSeekDelegateModelConfiguration())->normalize($runtimeModel, $configuration);
        $providerOperationStarted = false;
        try {
            return $this->credentialBroker->consume(
                $claim,
                $this->clock->now(),
                function (mixed $secret) use ($claim, $runtimeModel, $messages, $configuration, &$providerOperationStarted): string {
                    if (!is_string($secret) || '' === $secret) {
                        throw new \RuntimeException('CT321_DELEGATE_PROVIDER_CREDENTIAL_UNAVAILABLE');
                    }

                    $providerOperationStarted = true;
                    $this->journal->start($claim, $this->clock->now());
                    try {
                        $text = $this->platform->invoke(
                            $secret,
                            $runtimeModel,
                            $messages,
                            $configuration,
                            $claim['provider_request']['idempotency_key'],
                        );
                    } catch (\Throwable) {
                        $this->journal->markUnknown($claim, $this->clock->now());
                        throw new \RuntimeException('CT322_DELEGATE_PROVIDER_OUTCOME_UNKNOWN');
                    }

                    $sealedAt = $this->clock->now();
                    $this->responses->seal($claim, $text, $sealedAt);
                    $this->journal->sealResponse($claim, $text, $sealedAt);

                    return $text;
                },
            );
        } catch (\Throwable $exception) {
            if (!$providerOperationStarted) {
                $this->journal->markPreIoFailure($claim, 'CREDENTIAL_RESOLUTION_FAILED', $this->clock->now());
                throw new \RuntimeException('CT323_DELEGATE_PROVIDER_PRE_IO_FAILURE');
            }

            throw $exception;
        }
    }

    private function assertClaimScope(array $claim, string $runtimeModel): void
    {
        if ('INVOCATION_CLAIMED_PENDING_EXTERNAL_IO' !== ($claim['status'] ?? null)
            || true !== ($claim['lease_consumption']['consumed'] ?? null)
            || true !== ($claim['turn_authority_consumption']['consumed'] ?? null)
            || !is_string($claim['lease_consumption']['expires_at'] ?? null)
            || false !== ($claim['provider_request']['external_io_started'] ?? null)
            || !is_string($claim['provider_request']['idempotency_key'] ?? null)
            || DeepSeekDelegatePlatformAdapter::PROVIDER !== ($claim['model']['runtime_binding']['provider'] ?? null)
            || DeepSeekDelegatePlatformAdapter::PLATFORM_SERVICE !== ($claim['model']['runtime_binding']['platform_service'] ?? null)
            || DeepSeekDelegatePlatformAdapter::RUNTIME_MODEL !== $runtimeModel
            || $runtimeModel !== ($claim['model']['runtime_binding']['runtime_model'] ?? null)) {
            throw new \RuntimeException('CT320_DELEGATE_PROVIDER_CLAIM_SCOPE_INVALID');
        }
    }
}
