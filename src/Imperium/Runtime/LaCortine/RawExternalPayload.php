<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final readonly class RawExternalPayload
{
    /**
     * @param list<string> $sourceIds
     * @param list<string> $toolIds
     * @param list<string> $capabilityIds
     */
    public function __construct(
        public string $payloadId,
        public string $executionId,
        public string $commissionId,
        public string $authorizationId,
        public ?string $sortieId,
        public ?string $manifestationId,
        public string $content,
        public string $contentDigest,
        public array $sourceIds,
        public array $toolIds,
        public array $capabilityIds,
        public \DateTimeImmutable $observedAt,
        public \DateTimeImmutable $receivedAt,
    ) {
        foreach ([$payloadId, $executionId, $commissionId, $authorizationId, $contentDigest] as $value) {
            if ('' === trim($value)) {
                throw new \InvalidArgumentException('Raw external payloads require exact identity, authority lineage, and integrity evidence.');
            }
        }
        if (!hash_equals(hash('sha256', $content), strtolower($contentDigest))) {
            throw new \InvalidArgumentException('RAW_PAYLOAD_DIGEST_MISMATCH: supplied digest does not match raw content.');
        }
        if (($sortieId === null) !== ($manifestationId === null)) {
            throw new \InvalidArgumentException('Raw sortie payloads require both sortie and manifestation identities.');
        }
    }
}
