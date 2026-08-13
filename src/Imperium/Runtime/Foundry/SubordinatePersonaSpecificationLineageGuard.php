<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;

final readonly class SubordinatePersonaSpecificationLineageGuard
{
    public function __construct(private string $specificationDirectory)
    {
    }

    public function assertCurrent(array $specification): void
    {
        $id = $specification['specification_id'] ?? null;
        $digest = $specification['record_digest'] ?? null;
        if (!is_string($id) || !is_string($digest)) {
            throw new \RuntimeException('F137_SUBORDINATE_SPECIFICATION_LINEAGE_INVALID');
        }
        foreach (glob($this->specificationDirectory.'/subordinate-persona-specification-*.json') ?: [] as $path) {
            $candidate = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            $candidateDigest = $candidate['record_digest'] ?? null;
            $unsigned = $candidate;
            unset($unsigned['record_digest']);
            if (!is_string($candidateDigest) || !hash_equals($candidateDigest, hash('sha256', CanonicalJson::encode($unsigned)))) {
                throw new \RuntimeException('F137_SUBORDINATE_SPECIFICATION_LINEAGE_INVALID');
            }
            if ($id === ($candidate['supersedes']['specification_id'] ?? null)
                && $digest === ($candidate['supersedes']['specification_digest'] ?? null)
            ) {
                throw new \RuntimeException('F138_SUBORDINATE_SPECIFICATION_SUPERSEDED');
            }
        }
    }
}
