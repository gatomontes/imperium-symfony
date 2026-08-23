<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Conscription;

use App\Bootstrap\CanonicalJson;

final readonly class GenericOfficerSubstrateRegistry
{
    private string $path;

    public function __construct(string $projectDir)
    {
        $this->path = $projectDir.'/runtime/artifacts/generic-officer-substrate.json';
    }

    public function current(): array
    {
        if (!is_file($this->path)) {
            throw new \RuntimeException('R10_GENERIC_SUBSTRATE_ABSENT: canonical generic Officer substrate is unavailable.');
        }
        $artifact = json_decode((string) file_get_contents($this->path), true, 512, JSON_THROW_ON_ERROR);
        $digest = $artifact['content_digest'] ?? null;
        unset($artifact['content_digest']);
        if (!is_string($digest)
            || !hash_equals($digest, 'sha256:'.hash('sha256', CanonicalJson::encode($artifact)))
            || 'imperium.generic-officer-substrate/v1' !== ($artifact['schema'] ?? null)
            || 'generic-officer' !== ($artifact['id'] ?? null)
            || '1.0.0' !== ($artifact['version'] ?? null)
        ) {
            throw new \RuntimeException('R11_GENERIC_SUBSTRATE_INVALID: canonical generic Officer substrate is invalid.');
        }

        return ['id' => $artifact['id'], 'version' => $artifact['version'], 'content_digest' => $digest];
    }
}
