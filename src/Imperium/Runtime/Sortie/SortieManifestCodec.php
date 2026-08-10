<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Sortie;

use App\Imperium\Runtime\LaCortine\SortieManifest;

final class SortieManifestCodec
{
    public function seal(SortieManifest $manifest): SortieManifestEnvelope
    {
        $payload = $this->manifestPayload($manifest);

        return new SortieManifestEnvelope($manifest, hash('sha256', $this->encodePayload($payload)));
    }

    public function encode(SortieManifestEnvelope $envelope): string
    {
        $payload = $this->manifestPayload($envelope->manifest);
        $digest = hash('sha256', $this->encodePayload($payload));
        if (!hash_equals($envelope->manifestDigest, $digest)) {
            throw new \RuntimeException('SORTIE_MANIFEST_DIGEST_MISMATCH: envelope no longer matches the exact manifest.');
        }

        return json_encode([
            'schema' => 'imperium.sortie-manifest-envelope/v1',
            'manifest_digest' => $envelope->manifestDigest,
            'manifest' => $payload,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    public function decode(string $json, ?string $expectedDigest = null): SortieManifestEnvelope
    {
        $document = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($document)
            || 'imperium.sortie-manifest-envelope/v1' !== ($document['schema'] ?? null)
            || !is_array($document['manifest'] ?? null)
            || !is_string($document['manifest_digest'] ?? null)
        ) {
            throw new \RuntimeException('SORTIE_MANIFEST_INVALID: envelope shape or schema is invalid.');
        }

        $digest = hash('sha256', $this->encodePayload($document['manifest']));
        if (!hash_equals(strtolower($document['manifest_digest']), $digest)) {
            throw new \RuntimeException('SORTIE_MANIFEST_DIGEST_MISMATCH: manifest content was modified after sealing.');
        }
        if (null !== $expectedDigest && !hash_equals(strtolower($expectedDigest), $digest)) {
            throw new \RuntimeException('SORTIE_MANIFEST_UNEXPECTED: manifest does not match the boundary-issued digest.');
        }

        $m = $document['manifest'];
        $manifest = new SortieManifest(
            $this->string($m, 'execution_id'),
            $this->string($m, 'sortie_id'),
            $this->string($m, 'manifestation_id'),
            $this->string($m, 'commission_id'),
            $this->string($m, 'authorization_id'),
            $this->string($m, 'objective'),
            $this->string($m, 'context_digest'),
            $this->stringList($m, 'destinations'),
            $this->stringList($m, 'tool_ids'),
            $this->stringList($m, 'capability_ids'),
            $this->string($m, 'expected_return_contract'),
            new \DateTimeImmutable($this->string($m, 'expires_at')),
        );

        return new SortieManifestEnvelope($manifest, $digest);
    }

    private function manifestPayload(SortieManifest $manifest): array
    {
        return [
            'execution_id' => $manifest->executionId,
            'sortie_id' => $manifest->sortieId,
            'manifestation_id' => $manifest->manifestationId,
            'commission_id' => $manifest->commissionId,
            'authorization_id' => $manifest->authorizationId,
            'objective' => $manifest->objective,
            'context_digest' => $manifest->contextDigest,
            'destinations' => array_values($manifest->destinations),
            'tool_ids' => array_values($manifest->toolIds),
            'capability_ids' => array_values($manifest->capabilityIds),
            'expected_return_contract' => $manifest->expectedReturnContract,
            'expires_at' => $manifest->expiresAt->format(DATE_ATOM),
        ];
    }

    private function encodePayload(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    private function string(array $payload, string $key): string
    {
        if (!isset($payload[$key]) || !is_string($payload[$key]) || '' === trim($payload[$key])) {
            throw new \RuntimeException('SORTIE_MANIFEST_INVALID: missing exact string field '.$key.'.');
        }

        return $payload[$key];
    }

    /** @return list<string> */
    private function stringList(array $payload, string $key): array
    {
        if (!isset($payload[$key]) || !is_array($payload[$key])) {
            throw new \RuntimeException('SORTIE_MANIFEST_INVALID: missing list field '.$key.'.');
        }
        foreach ($payload[$key] as $value) {
            if (!is_string($value) || '' === trim($value)) {
                throw new \RuntimeException('SORTIE_MANIFEST_INVALID: '.$key.' must contain exact non-empty strings.');
            }
        }

        return array_values($payload[$key]);
    }
}
