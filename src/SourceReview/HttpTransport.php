<?php
declare(strict_types=1);
namespace App\SourceReview;

use Symfony\Component\HttpClient\HttpClient;

final class HttpTransport implements Transport
{
    /** Production always constructs the undecorated client; the explicit seam accepts
     * only Symfony's network-free mock for offline wire-contract tests. */
    public function __construct(private ?\Symfony\Component\HttpClient\MockHttpClient $offlineClient = null) {}
    public function send(string $secret, string $payload): string
    {
        // Dedicated undecorated client: no RetryableHttpClient, SDK, redirects or tools.
        $client = ($this->offlineClient ?? HttpClient::create())->withOptions(['timeout' => 120, 'max_duration' => 120, 'max_redirects' => 0, 'http_version' => '1.1']);
        $response = $client->request('POST', Proposal::ENDPOINT, [
            'headers' => ['Authorization' => 'Bearer '.$secret, 'Content-Type' => 'application/json'],
            'body' => $payload,
        ]);
        try {
            $raw = '';
            foreach ($client->stream($response) as $chunk) {
                $raw .= $chunk->getContent();
                if (strlen($raw) > 524288) { throw new \RuntimeException('SR_RESPONSE_SIZE'); }
            }
            if ($response->getStatusCode() !== 200) { throw new \RuntimeException('SR_PROVIDER_HTTP_ERROR'); }
            $r = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
            if (($r['choices'][0]['finish_reason'] ?? null) !== 'stop' || !is_string($r['choices'][0]['message']['content'] ?? null)
                || !is_int($r['usage']['prompt_tokens'] ?? null) || !is_int($r['usage']['completion_tokens'] ?? null)
                || $r['usage']['prompt_tokens'] > 32000 || $r['usage']['completion_tokens'] > 4000) { throw new \RuntimeException('SR_PROVIDER_RESPONSE_INVALID'); }
            return $r['choices'][0]['message']['content'];
        } finally { $response->cancel(); }
    }
}
