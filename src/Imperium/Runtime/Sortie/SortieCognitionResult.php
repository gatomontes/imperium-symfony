<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Sortie;

final readonly class SortieCognitionResult
{
    /**
     * @param list<string> $sourceIds
     * @param list<string> $toolIds
     * @param list<string> $capabilityIds
     */
    public function __construct(
        public string $content,
        public array $sourceIds,
        public array $toolIds,
        public array $capabilityIds,
        public \DateTimeImmutable $observedAt,
    ) {
    }
}
