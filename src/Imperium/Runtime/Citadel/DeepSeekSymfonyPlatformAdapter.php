<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel;

use Symfony\AI\Platform\Bridge\Generic\Factory;
use Symfony\AI\Platform\Bridge\Generic\ModelCatalog;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class DeepSeekSymfonyPlatformAdapter implements DelegateSymfonyPlatformAdapter
{
    public function __construct(
        private ModelCatalog $modelCatalog,
        #[Autowire(service: 'ai.deepseek.client')]
        private HttpClientInterface $httpClient,
    ) {
    }

    public function invoke(
        string $secret,
        string $runtimeModel,
        MessageBag $messages,
        array $configuration,
        string $idempotencyKey,
    ): string {
        $client = $this->httpClient->withOptions([
            'headers' => ['Idempotency-Key' => $idempotencyKey],
        ]);
        $platform = Factory::createPlatform(
            baseUrl: 'https://api.deepseek.com',
            apiKey: $secret,
            httpClient: $client,
            modelCatalog: $this->modelCatalog,
            supportsEmbeddings: false,
            name: 'deepseek',
        );

        return $platform->invoke($runtimeModel, $messages, $configuration)->asText();
    }
}
