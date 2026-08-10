<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class RawExternalPayloadCodec
{
    public function encode(RawExternalPayload $payload): string
    {
        return json_encode([
            'schema' => 'imperium.raw-external-payload/v1',
            'payload_id' => $payload->payloadId,
            'execution_id' => $payload->executionId,
            'commission_id' => $payload->commissionId,
            'authorization_id' => $payload->authorizationId,
            'sortie_id' => $payload->sortieId,
            'manifestation_id' => $payload->manifestationId,
            'content' => $payload->content,
            'content_digest' => $payload->contentDigest,
            'source_ids' => array_values($payload->sourceIds),
            'tool_ids' => array_values($payload->toolIds),
            'capability_ids' => array_values($payload->capabilityIds),
            'observed_at' => $payload->observedAt->format(DATE_ATOM),
            'received_at' => $payload->receivedAt->format(DATE_ATOM),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    public function decode(string $json): RawExternalPayload
    {
        $d = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($d) || 'imperium.raw-external-payload/v1' !== ($d['schema'] ?? null)) {
            throw new \RuntimeException('RAW_PAYLOAD_INVALID: unsupported payload schema.');
        }

        return new RawExternalPayload(
            $this->string($d, 'payload_id'),
            $this->string($d, 'execution_id'),
            $this->string($d, 'commission_id'),
            $this->string($d, 'authorization_id'),
            $this->nullableString($d, 'sortie_id'),
            $this->nullableString($d, 'manifestation_id'),
            $this->string($d, 'content', false),
            $this->string($d, 'content_digest'),
            $this->stringList($d, 'source_ids'),
            $this->stringList($d, 'tool_ids'),
            $this->stringList($d, 'capability_ids'),
            new \DateTimeImmutable($this->string($d, 'observed_at')),
            new \DateTimeImmutable($this->string($d, 'received_at')),
        );
    }

    private function string(array $d, string $key, bool $nonEmpty = true): string
    {
        if (!array_key_exists($key, $d) || !is_string($d[$key]) || ($nonEmpty && '' === trim($d[$key]))) {
            throw new \RuntimeException('RAW_PAYLOAD_INVALID: invalid string field '.$key.'.');
        }
        return $d[$key];
    }

    private function nullableString(array $d, string $key): ?string
    {
        if (!array_key_exists($key, $d) || null === $d[$key]) {
            return null;
        }
        if (!is_string($d[$key]) || '' === trim($d[$key])) {
            throw new \RuntimeException('RAW_PAYLOAD_INVALID: invalid nullable string field '.$key.'.');
        }
        return $d[$key];
    }

    /** @return list<string> */
    private function stringList(array $d, string $key): array
    {
        if (!isset($d[$key]) || !is_array($d[$key])) {
            throw new \RuntimeException('RAW_PAYLOAD_INVALID: invalid list field '.$key.'.');
        }
        foreach ($d[$key] as $value) {
            if (!is_string($value) || '' === trim($value)) {
                throw new \RuntimeException('RAW_PAYLOAD_INVALID: '.$key.' must contain exact non-empty strings.');
            }
        }
        return array_values($d[$key]);
    }
}
