<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class InboundLazaretto
{
    private const MAX_BYTES = 5_000_000;

    public function admit(InboundExternalPayload $payload, \DateTimeImmutable $admittedAt): AdmittedInboundArtifact
    {
        if (strlen($payload->content) > self::MAX_BYTES) {
            throw new \RuntimeException('LAZARETTO_INBOUND_TOO_LARGE: raw external payload exceeds admission limit.');
        }
        if ('agentmail.webhook' !== $payload->source) {
            throw new \RuntimeException('LAZARETTO_INBOUND_SOURCE_UNSUPPORTED: inbound source is not admitted.');
        }

        $decoded = json_decode($payload->content, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('LAZARETTO_INBOUND_MALFORMED: AgentMail payload must be valid JSON.');
        }

        // We validate the transport envelope only. Message text remains untrusted evidence;
        // it is never interpreted here as authority or instruction.
        $eventType = $decoded['event_type'] ?? $decoded['type'] ?? null;
        if (!is_string($eventType) || '' === trim($eventType)) {
            throw new \RuntimeException('LAZARETTO_INBOUND_EVENT_MISSING: provider event type is required.');
        }

        $normalized = json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $provenance = [
            'source' => $payload->source,
            'event_type' => $eventType,
            'transport_metadata' => $payload->transportMetadata,
            'received_at' => $payload->receivedAt->format(DATE_ATOM),
            'raw_payload_digest' => $payload->contentDigest,
            'content_trust' => 'untrusted-external-evidence',
            'authority' => 'none',
            'transformation' => 'json-normalize-v1',
        ];

        return new AdmittedInboundArtifact(
            'inbound-artifact.'.hash('sha256', $payload->payloadId.'|'.$payload->contentDigest.'|'.$admittedAt->format(DATE_ATOM)),
            $payload->payloadId,
            $payload->contentDigest,
            $normalized,
            $provenance,
            $admittedAt,
        );
    }
}
