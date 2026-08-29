<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

interface BoundProviderEvidenceDecoder
{
    public function supports(array $binding): bool;

    public function decode(array $binding, array $rawResult, string $rawContent, \DateTimeImmutable $decodedAt): array;
}
