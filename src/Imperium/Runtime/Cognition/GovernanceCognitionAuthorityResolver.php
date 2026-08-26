<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Cognition;

interface GovernanceCognitionAuthorityResolver
{
    public function supports(string $cluster, string $authorityType): bool;

    /** Returns a normalized view reread from the authoritative cluster record. */
    public function resolve(string $cluster, string $authorityType, string $authorityId): array;
}
