<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel;

use App\Imperium\Runtime\Clavium\ProviderInvocationJournalService;
use App\Imperium\Runtime\LaCortine\CredentialBroker;
use Symfony\AI\Platform\Bridge\Generic\Factory;
use Symfony\AI\Platform\Bridge\Generic\ModelCatalog;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class SymfonyAiBrokeredDelegateProviderInvoker implements DelegateProviderInvoker
{
    public function __construct(
        private CredentialBroker $credentialBroker,
        private ProviderInvocationJournalService $journal,
        private ModelCatalog $modelCatalog,
        #[Autowire(service: 'ai.deepseek.client')]
        private HttpClientInterface $httpClient,
    ) {
    }

    public function invoke(
        array $claim,
        string $runtimeModel,
        MessageBag $messages,
        array $configuration,
    ): string {
        $this->assertClaimScope($claim, $runtimeModel);
        $expiresAt = new \DateTimeImmutable($claim['lease_consumption']['expires_at']);
        $capability = $this->credentialBroker->issue(
            'env:DEEPSEEK_API_KEY',
            $claim['claim_id'],
            'deepseek.model.invoke',
            $expiresAt,
        );

        return $this->credentialBroker->consume(
            $capability,
            function (mixed $secret) use ($claim, $runtimeModel, $messages, $configuration): string {
                if (!is_string($secret) || '' === $secret) {
                    throw new \RuntimeException('CT321_DELEGATE_PROVIDER_CREDENTIAL_UNAVAILABLE');
                }

                $this->journal->start($claim, new \DateTimeImmutable());
                try {
                    $client = $this->httpClient->withOptions([
                        'headers' => ['Idempotency-Key' => $claim['provider_request']['idempotency_key']],
                    ]);
                    $platform = Factory::createPlatform(
                        baseUrl: 'https://api.deepseek.com',
                        apiKey: $secret,
                        httpClient: $client,
                        modelCatalog: $this->modelCatalog,
                        supportsEmbeddings: false,
                        name: 'deepseek',
                    );
                    $text = $platform->invoke($runtimeModel, $messages, $configuration)->asText();
                } catch (\Throwable $exception) {
                    $this->journal->markUnknown($claim, new \DateTimeImmutable());
                    throw new \RuntimeException('CT322_DELEGATE_PROVIDER_OUTCOME_UNKNOWN');
                }

                $this->journal->sealResponse($claim, $text, new \DateTimeImmutable());

                return $text;
            },
        );
    }

    private function assertClaimScope(array $claim, string $runtimeModel): void
    {
        if ('INVOCATION_CLAIMED_PENDING_EXTERNAL_IO' !== ($claim['status'] ?? null)
            || true !== ($claim['lease_consumption']['consumed'] ?? null)
            || true !== ($claim['turn_authority_consumption']['consumed'] ?? null)
            || !is_string($claim['lease_consumption']['expires_at'] ?? null)
            || false !== ($claim['provider_request']['external_io_started'] ?? null)
            || !is_string($claim['provider_request']['idempotency_key'] ?? null)
            || 'deepseek' !== ($claim['model']['runtime_binding']['provider'] ?? null)
            || 'ai.platform.generic.deepseek' !== ($claim['model']['runtime_binding']['platform_service'] ?? null)
            || $runtimeModel !== ($claim['model']['runtime_binding']['runtime_model'] ?? null)) {
            throw new \RuntimeException('CT320_DELEGATE_PROVIDER_CLAIM_SCOPE_INVALID');
        }
    }
}
