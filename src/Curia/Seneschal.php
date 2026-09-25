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
            if ('assistant' === $exchange['role']) {
                // Stored text includes the application's permission question. Rebuild
                // the model's JSON reply for history without that display-only suffix.
                $suffix = "\n\n".Interview::PERMISSION_QUESTION;
                $ready = str_ends_with($exchange['text'], $suffix);
                $text = $ready ? substr($exchange['text'], 0, -\strlen($suffix)) : $exchange['text'];
                $messages->add(Message::ofAssistant(json_encode([
                    'message' => $text,
                    'readyToDraft' => $ready,
                ], \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE)));
            } else {
                $messages->add(Message::ofUser($exchange['text']));
            }
        }
        $result = $this->agent->call($messages, [
            'response_format' => ['type' => 'json_object'],
            'max_tokens' => 2048,
            'thinking' => ['type' => 'disabled'],
        ])->getContent();
        // Diagnose the shape without including any model text in the error.
        if (!\is_array($result)) {
            throw new InvalidSeneschalReply('Reply format: expected a JSON object.');
        }
        foreach (['message' => 'string', 'readyToDraft' => 'boolean'] as $field => $type) {
            if (!array_key_exists($field, $result)) {
                throw new InvalidSeneschalReply('Reply format: missing required field "'.$field.'".');
            }
            if (gettype($result[$field]) !== $type) {
                throw new InvalidSeneschalReply('Reply format: "'.$field.'" must be '.$type.'.');
            }
        }
        if ('' === trim($result['message'])) {
            throw new InvalidSeneschalReply('Reply format: "message" is empty.');
        }
        // The platform decodes JSON mode; Symfony enforces the typed reply contract.
        $result = $this->denormalizer->denormalize($result, SeneschalReply::class);
        if (!$result instanceof SeneschalReply || \count($this->validator->validate($result)) > 0) {
            throw new InvalidSeneschalReply('Reply format: "message" exceeds the allowed length.');
        }

        return $result;
    }
}
