<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final readonly class AgentMailWebhookVerifier
{
    private const TOLERANCE_SECONDS = 300;

    public function __construct(private string $signingSecret)
    {
    }

    /**
     * @param array<string, string> $headers lowercase header names
     */
    public function verify(string $rawBody, array $headers, ?int $now = null): string
    {
        if (!str_starts_with($this->signingSecret, 'whsec_')) {
            throw new \RuntimeException('AGENTMAIL_WEBHOOK_SECRET_INVALID: signing secret must use the whsec_ format.');
        }

        $messageId = $headers['svix-id'] ?? $headers['webhook-id'] ?? null;
        $timestamp = $headers['svix-timestamp'] ?? $headers['webhook-timestamp'] ?? null;
        $signatures = $headers['svix-signature'] ?? $headers['webhook-signature'] ?? null;
        if (!is_string($messageId) || '' === $messageId
            || !is_string($timestamp) || !ctype_digit($timestamp)
            || !is_string($signatures) || '' === trim($signatures)
        ) {
            throw new \RuntimeException('AGENTMAIL_WEBHOOK_SIGNATURE_MISSING: required Svix verification headers are absent.');
        }

        $now ??= time();
        if (abs($now - (int) $timestamp) > self::TOLERANCE_SECONDS) {
            throw new \RuntimeException('AGENTMAIL_WEBHOOK_TIMESTAMP_REJECTED: webhook timestamp is outside the five-minute replay window.');
        }

        $encodedSecret = substr($this->signingSecret, strlen('whsec_'));
        $padding = (4 - strlen($encodedSecret) % 4) % 4;
        $key = base64_decode($encodedSecret.str_repeat('=', $padding), true);
        if (false === $key) {
            throw new \RuntimeException('AGENTMAIL_WEBHOOK_SECRET_INVALID: signing secret payload is not valid base64.');
        }

        $signedContent = $messageId.'.'.$timestamp.'.'.$rawBody;
        $expected = base64_encode(hash_hmac('sha256', $signedContent, $key, true));

        foreach (preg_split('/\s+/', trim($signatures)) ?: [] as $candidate) {
            if (!str_starts_with($candidate, 'v1,')) {
                continue;
            }
            $provided = substr($candidate, 3);
            if ('' !== $provided && hash_equals($expected, $provided)) {
                return $messageId;
            }
        }

        throw new \RuntimeException('AGENTMAIL_WEBHOOK_SIGNATURE_INVALID: webhook signature verification failed.');
    }
}
