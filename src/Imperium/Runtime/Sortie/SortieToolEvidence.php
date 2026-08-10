<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Sortie;

final readonly class SortieToolEvidence
{
    public function __construct(
        public string $content,
        public string $contentDigest,
        public string $sourceId,
        public string $toolId,
        public string $capabilityId,
        public \DateTimeImmutable $observedAt,
    ) {
        if (!hash_equals(hash('sha256', $content), $contentDigest)) {
            throw new \InvalidArgumentException('SORTIE_TOOL_EVIDENCE_DIGEST_MISMATCH: evidence digest does not match raw bytes.');
        }
    }
}
