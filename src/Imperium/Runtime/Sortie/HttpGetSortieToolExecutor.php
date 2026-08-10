<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Sortie;

use App\Imperium\Runtime\LaCortine\SortieManifest;

final class HttpGetSortieToolExecutor implements SortieToolExecutor
{
    private bool $consumed = false;

    public function execute(SortieManifest $manifest): SortieToolEvidence
    {
        if ($this->consumed) {
            throw new \RuntimeException('SORTIE_TOOL_CAPABILITY_CONSUMED: the one-use HTTP GET capability has already been consumed.');
        }
        if (['http.get'] !== array_values($manifest->toolIds)) {
            throw new \RuntimeException('SORTIE_TOOL_SCOPE_INVALID: this executor requires exactly one declared http.get tool.');
        }
        if (1 !== count($manifest->capabilityIds)) {
            throw new \RuntimeException('SORTIE_TOOL_CAPABILITY_INVALID: http.get requires exactly one declared capability.');
        }
        if (1 !== count($manifest->destinations)) {
            throw new \RuntimeException('SORTIE_TOOL_DESTINATION_INVALID: http.get requires exactly one authorized destination.');
        }

        $destination = $manifest->destinations[0];
        $url = parse_url($destination);
        if (!is_array($url) || 'https' !== strtolower((string) ($url['scheme'] ?? '')) || '' === (string) ($url['host'] ?? '')) {
            throw new \RuntimeException('SORTIE_TOOL_DESTINATION_REJECTED: http.get requires an exact HTTPS destination.');
        }

        // Consume before external execution so failure cannot be replayed in this one-shot runtime.
        $this->consumed = true;

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'Accept: text/plain, application/json, text/html;q=0.9, */*;q=0.1',
                    'Connection: close',
                ],
                'ignore_errors' => true,
                'follow_location' => 0,
                'timeout' => 20,
            ],
        ]);

        $content = @file_get_contents($destination, false, $context);
        if (false === $content) {
            throw new \RuntimeException('SORTIE_TOOL_HTTP_GET_FAILED: authorized destination returned no readable response.');
        }

        $status = $this->statusCode($http_response_header ?? []);
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException(sprintf('SORTIE_TOOL_HTTP_GET_REJECTED: authorized destination returned HTTP %d.', $status));
        }

        return new SortieToolEvidence(
            $content,
            hash('sha256', $content),
            $destination,
            'http.get',
            $manifest->capabilityIds[0],
            new \DateTimeImmutable(),
        );
    }

    /** @param list<string> $headers */
    private function statusCode(array $headers): int
    {
        if ([] === $headers || 1 !== preg_match('/^HTTP\/\S+\s+(\d{3})\b/', $headers[0], $matches)) {
            throw new \RuntimeException('SORTIE_TOOL_HTTP_RESPONSE_INVALID: response status line is missing.');
        }

        return (int) $matches[1];
    }
}
