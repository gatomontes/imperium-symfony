<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;

final readonly class AgentMailProviderRequestEncoder
{
    public function encode(array $binding, string $destination, string $payload, mixed $opaqueAuthentication, string $idempotencyKey): AgentMailTransientEncodedRequest
    {
        if (AgentMailProviderProfile::PROVIDER_ID !== ($binding['provider_implementation']['provider_id'] ?? null)
            || AgentMailProviderProfile::ADAPTER_ID !== ($binding['provider_implementation']['adapter_id'] ?? null)
            || AgentMailProviderProfile::ADAPTER_VERSION !== ($binding['provider_implementation']['adapter_version'] ?? null)
            || AgentMailProviderProfile::CREDENTIAL_FAMILY_ID !== ($binding['credential_family']['family_id'] ?? null)
            || AgentMailProviderProfile::REQUEST_ENCODER_ID !== ($binding['request_encoder']['id'] ?? null)
            || false !== ($binding['credential_family']['secret_persistence_permitted'] ?? null)
            || !is_string($opaqueAuthentication) || '' === $opaqueAuthentication
            || '' === trim($idempotencyKey)) {
            throw new \RuntimeException('GTP400_AGENTMAIL_ENCODER_BINDING_INVALID');
        }
        if (1 !== preg_match(AgentMailProviderProfile::ENDPOINT_PATTERN, $destination)) {
            throw new \RuntimeException('GTP401_AGENTMAIL_ENCODER_DESTINATION_REJECTED');
        }
        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException('GTP402_AGENTMAIL_ENCODER_PAYLOAD_INVALID', 0, $exception);
        }
        if (!is_array($decoded) || !is_array($decoded['to'] ?? null) || [] === $decoded['to']) {
            throw new \RuntimeException('GTP402_AGENTMAIL_ENCODER_PAYLOAD_INVALID');
        }

        $bindingReference = ['id' => $binding['binding_id'], 'digest' => $binding['record_digest'], 'schema' => $binding['schema']];
        $safeHeaderShape = ['Authorization' => AgentMailProviderProfile::AUTHORIZATION_SCHEME.' <opaque>', 'Content-Type' => 'application/json', 'Accept' => 'application/json', 'Idempotency-Key' => '<exact>'];
        $bodyDigest = hash('sha256', $payload);
        $headersDigest = hash('sha256', CanonicalJson::encode($safeHeaderShape));
        $evidence = [
            'schema' => ProviderRequestEncoderContract::SCHEMA,
            'encoder_id' => AgentMailProviderProfile::REQUEST_ENCODER_ID,
            'encoder_version' => AgentMailProviderProfile::ADAPTER_VERSION,
            'provider_binding' => $bindingReference,
            'method' => 'POST',
            'destination' => $destination,
            'headers_digest' => $headersDigest,
            'body_digest' => $bodyDigest,
            'request_fingerprint' => hash('sha256', CanonicalJson::encode([$bindingReference, 'POST', $destination, $headersDigest, $bodyDigest, hash('sha256', $idempotencyKey)])),
            'secret_persistence_permitted' => false,
        ];

        return new AgentMailTransientEncodedRequest([
            'method' => 'POST',
            'url' => $destination,
            'headers' => [
                'Authorization' => AgentMailProviderProfile::AUTHORIZATION_SCHEME.' '.$opaqueAuthentication,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Idempotency-Key' => $idempotencyKey,
            ],
            'body' => $payload,
        ], $evidence);
    }
}
