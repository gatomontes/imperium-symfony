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

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Authorization: Bearer '.$authentication,
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Connection: close',
                ],
                'content' => $payload,
                'ignore_errors' => true,
                'timeout' => 30,
                'follow_location' => 0,
                'max_redirects' => 0,
            ],
        ]);

        $response = @file_get_contents($destination, false, $context);
        if (false === $response) {
            throw new \RuntimeException('AGENTMAIL_REQUEST_FAILED: deterministic email request failed before a response body was returned.');
        }

        $status = $this->statusCode($http_response_header ?? []);
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException(sprintf('AGENTMAIL_REJECTED: AgentMail returned HTTP %d.', $status));
        }

        try {
            $receipt = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException('AGENTMAIL_RECEIPT_INVALID: provider response was not valid JSON.', 0, $e);
        }
        if (!is_array($receipt)
            || !is_string($receipt['message_id'] ?? null)
            || '' === $receipt['message_id']
            || !is_string($receipt['thread_id'] ?? null)
            || '' === $receipt['thread_id']
        ) {
            throw new \RuntimeException('AGENTMAIL_RECEIPT_INVALID: provider receipt omitted message_id or thread_id.');
        }

        return new TransportResult(
            $response,
            [
                'provider:agentmail',
                'provider-message:'.$receipt['message_id'],
                'provider-thread:'.$receipt['thread_id'],
                $destination,
                'http-status:'.$status,
            ],
            new \DateTimeImmutable(),
        );
    }

    /** @param list<string> $headers */
    private function statusCode(array $headers): int
    {
        if ([] === $headers || 1 !== preg_match('/^HTTP\/\S+\s+(\d{3})\b/', $headers[0], $matches)) {
            throw new \RuntimeException('AGENTMAIL_RECEIPT_INVALID: response status line is missing.');
        }

        return (int) $matches[1];
    }
}
