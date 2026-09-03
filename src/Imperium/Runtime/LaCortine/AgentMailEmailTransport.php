<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class AgentMailEmailTransport implements DeterministicTransport
{
    public function supports(string $operation): bool
    {
        return 'email.send' === $operation;
    }

    public function execute(
        string $operation,
        string $destination,
        string $payload,
        mixed $authentication,
    ): TransportResult {
        if (!$this->supports($operation)) {
            throw new \InvalidArgumentException('AgentMailEmailTransport supports only email.send.');
        }
        if (!is_string($authentication) || '' === $authentication) {
            throw new \RuntimeException('AGENTMAIL_AUTHENTICATION_UNAVAILABLE: bearer credential was not supplied by the boundary broker.');
        }

        $url = parse_url($destination);
        $path = is_array($url) ? (string) ($url['path'] ?? '') : '';
        if (!is_array($url)
            || 'https' !== strtolower((string) ($url['scheme'] ?? ''))
            || 'api.agentmail.to' !== strtolower((string) ($url['host'] ?? ''))
            || isset($url['user'])
            || isset($url['pass'])
            || isset($url['query'])
            || isset($url['fragment'])
            || 1 !== preg_match('#^/v0/inboxes/[^/]+/messages/send$#', $path)
        ) {
            throw new \RuntimeException('AGENTMAIL_DESTINATION_REJECTED: email.send requires an exact AgentMail inbox send endpoint.');
        }

        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException('AGENTMAIL_PAYLOAD_INVALID: email payload must be valid JSON.', 0, $e);
        }
        if (!is_array($decoded) || !array_key_exists('to', $decoded)) {
            throw new \RuntimeException('AGENTMAIL_PAYLOAD_INVALID: exact email payload must declare at least one recipient.');
        }

        // This legacy signature has no stored claim, binding root or native admission.
        throw new \RuntimeException('CCI_EMAIL_TRANSPORT_HAS_NO_BINDING_ROOT');

    }
}
