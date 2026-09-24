<?php

namespace App\Curia;

use App\Entity\Interview;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Seneschal
{
    public function __construct(
        #[Autowire(service: 'ai.agent.seneschal')]
        private AgentInterface $agent,
        private ValidatorInterface $validator,
        private DenormalizerInterface $denormalizer,
        #[Autowire(env: 'DEEPSEEK_API_KEY')]
        #[\SensitiveParameter]
        private string $apiKey,
    ) {
    }

    public function assertConfigured(): void
    {
        if ('' === trim($this->apiKey)) {
            throw new \DomainException('Set DEEPSEEK_API_KEY in .env.local before requesting a reply.');
        }
    }

    public function reply(Interview $interview): SeneschalReply
    {
        $messages = new MessageBag();
        foreach ($interview->getExchanges() as $exchange) {
            $messages->add('assistant' === $exchange['role'] ? Message::ofAssistant($exchange['text']) : Message::ofUser($exchange['text']));
        }
        $result = $this->agent->call($messages, [
            'response_format' => ['type' => 'json_object'],
            'max_tokens' => 2048,
            'thinking' => ['type' => 'disabled'],
        ])->getContent();
        // The platform decodes JSON mode; Symfony enforces the typed reply contract.
        $result = $this->denormalizer->denormalize($result, SeneschalReply::class);
        if (!$result instanceof SeneschalReply || \count($this->validator->validate($result)) > 0) {
            throw new \UnexpectedValueException('Seneschal returned an invalid interview response.');
        }

        return $result;
    }
}
