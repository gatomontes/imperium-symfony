<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final readonly class AdmittedInboundArtifact
{
    /** @param array<string, mixed> $provenance */
    public function __construct(
        public string $artifactId,
        public string $rawPayloadId,
        public string $rawPayloadDigest,
        public string $rawContent,
        public string $content,
        public array $provenance,
        public \DateTimeImmutable $admittedAt,
    ) {
    }
}
