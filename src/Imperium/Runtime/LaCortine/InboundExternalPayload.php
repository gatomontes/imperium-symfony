<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final readonly class InboundExternalPayload
{
    public string $contentDigest;

    /** @param array<string, scalar|null> $transportMetadata */
    public function __construct(
        public string $payloadId,
        public string $source,
        public string $content,
        public array $transportMetadata,
        public \DateTimeImmutable $receivedAt,
    ) {
        if ('' === trim($payloadId) || '' === trim($source) || '' === $content) {
            throw new \InvalidArgumentException('Inbound payload identity, source and content are required.');
        }
        $this->contentDigest = hash('sha256', $content);
    }
}
