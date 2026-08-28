<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final readonly class AgentMailIdempotencyHeaderAdapter
{
    public function invoke(array $journal, string $destination, string $payload, mixed $authentication, callable $providerCallback): mixed
    {
        $key = $journal['provider_safety']['provider_idempotency_key'] ?? null;
        if (!is_string($authentication) || '' === $authentication || !is_string($key) || '' === trim($key)) {
            throw new \RuntimeException('IGA600_AGENTMAIL_INVOCATION_CONTEXT_INVALID');
        }
        $url = parse_url($destination);
        $path = is_array($url) ? (string) ($url['path'] ?? '') : '';
        if (!is_array($url)
            || 'https' !== strtolower((string) ($url['scheme'] ?? ''))
            || 'api.agentmail.to' !== strtolower((string) ($url['host'] ?? ''))
            || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])
            || 1 !== preg_match('#^/v0/inboxes/[^/]+/messages/send$#', $path)) {
            throw new \RuntimeException('IGA601_AGENTMAIL_DESTINATION_REJECTED');
        }
        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException('IGA602_AGENTMAIL_PAYLOAD_INVALID', 0, $exception);
        }
        if (!is_array($decoded) || !array_key_exists('to', $decoded)) {
            throw new \RuntimeException('IGA602_AGENTMAIL_PAYLOAD_INVALID');
        }

        return $providerCallback([
            'method' => 'POST',
            'url' => $destination,
            'headers' => [
                'Authorization' => 'Bearer '.$authentication,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Idempotency-Key' => $key,
            ],
            'body' => $payload,
        ]);
    }
}
