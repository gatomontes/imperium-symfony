<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Mission;

/** Verified read-only handle to one exact persisted Mission Authorization chain. */
final readonly class AuthenticatedMissionAuthorization
{
    public function __construct(
        public string $authorizationId,
        public string $authorizationDigest,
        public string $dossierId,
        public int $dossierVersion,
        public string $dossierDigest,
        public string $reviewId,
        public string $reviewDigest,
        public string $operatorIdentity,
        public string $approvedAt,
        public CanonicalMissionPlan $mission,
    ) {}
}
