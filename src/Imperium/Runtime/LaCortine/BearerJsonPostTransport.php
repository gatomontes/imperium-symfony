<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class BearerJsonPostTransport implements DeterministicTransport
{
    public function supports(string $operation): bool
    {
        return 'http.post.json' === $operation;
    }

    public function execute(
        string $operation,
        string $destination,
        string $payload,
        mixed $authentication,
    ): TransportResult {
        if (!$this->supports($operation)) {
            throw new \InvalidArgumentException('BearerJsonPostTransport supports only http.post.json.');
        }
        if (!is_string($authentication) || '' === $authentication) {
            throw new \RuntimeException('HTTP_AUTHENTICATION_UNAVAILABLE: bearer credential was not supplied by the boundary broker.');
        }

        $url = parse_url($destination);
        if (!is_array($url) || 'https' !== strtolower((string) ($url['scheme'] ?? '')) || '' === (string) ($url['host'] ?? '')) {
            throw new \RuntimeException('HTTP_DESTINATION_REJECTED: deterministic bearer transport requires an exact HTTPS destination.');
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
            ],
        ]);

        $response = @file_get_contents($destination, false, $context);
        if (false === $response) {
            throw new \RuntimeException('HTTP_PROVIDER_REQUEST_FAILED: deterministic external request failed before a response body was returned.');
        }

        $status = $this->statusCode($http_response_header ?? []);
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException(sprintf('HTTP_PROVIDER_REJECTED: external service returned HTTP %d.', $status));
        }

        return new TransportResult(
            $response,
            [$destination, 'http-status:'.$status],
            new \DateTimeImmutable(),
        );
    }

    /** @param list<string> $headers */
    private function statusCode(array $headers): int
    {
        if ([] === $headers || 1 !== preg_match('/^HTTP\/\S+\s+(\d{3})\b/', $headers[0], $matches)) {
            throw new \RuntimeException('HTTP_PROVIDER_RESPONSE_INVALID: response status line is missing.');
        }

        return (int) $matches[1];
    }
}
