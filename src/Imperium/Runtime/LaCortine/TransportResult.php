<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final readonly class TransportResult
{
    /** @param list<string> $sourceIds */
    public function __construct(
        public string $content,
        public array $sourceIds,
        public \DateTimeImmutable $observedAt,
    ) {
        if ([] === $sourceIds) {
            throw new \InvalidArgumentException('Deterministic transport results require at least one external source identifier.');
        }
    }
}
