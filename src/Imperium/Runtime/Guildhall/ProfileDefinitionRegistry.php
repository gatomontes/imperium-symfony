<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Guildhall;

use App\Bootstrap\CanonicalJson;

final readonly class ProfileDefinitionRegistry
{
    private string $projectDir;
    private string $directory;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
        $this->directory = $projectDir.'/offices/guildhall/profile-definitions';
    }

    public function current(string $name, string $seat): array
    {
        $definition = $this->read($this->directory.'/'.$name.'.json');
        $approval = $this->read($this->directory.'/'.$name.'.approved.json');
        $current = $this->read($this->directory.'/'.$name.'.current.json');
        if (!$this->digestMatches($definition, 'content_digest')
            || !$this->digestMatches($approval, 'record_digest')
            || !$this->digestMatches($current, 'record_digest')
            || 'officer_profile_definition' !== ($definition['artifact_class'] ?? null)
            || 'guildhall' !== ($definition['steward']['id'] ?? null)
            || $seat !== ($definition['target']['id'] ?? null)
            || 'approved' !== ($approval['transition']['to'] ?? null)
            || 'imperator' !== ($approval['actor']['kind'] ?? null)
            || 'current' !== ($current['transition']['to'] ?? null)
            || 'approved' !== ($current['transition']['from'] ?? null)
            || 'guildhall.profile-registry' !== ($current['actor']['id'] ?? null)
            || ($approval['attestation_id'] ?? null) !== ($current['transition']['prior_attestation_id'] ?? null)
        ) {
            throw new \RuntimeException('G20_PROFILE_DEFINITION_INVALID: '.$seat.' definition or lifecycle chain is invalid.');
        }
        $reference = [
            'definition_id' => $definition['definition_id'] ?? null,
            'definition_version' => $definition['definition_version'] ?? null,
            'content_digest' => $definition['content_digest'] ?? null,
        ];
        if (!$this->sameReference($reference, $approval['definition_ref'] ?? null)
            || !$this->sameReference($reference, $current['definition_ref'] ?? null)
        ) {
            throw new \RuntimeException('G20_PROFILE_DEFINITION_INVALID: '.$seat.' attestations reference another definition.');
        }
        $sourcePath = $definition['source']['path'] ?? null;
        if (!is_string($sourcePath) || !is_file($this->projectDir.'/'.$sourcePath)) {
            throw new \RuntimeException('G21_PROFILE_DEFINITION_SOURCE_ABSENT: '.$seat.' source is unavailable.');
        }
        $sourceDigest = 'sha256:'.hash('sha256', (string) file_get_contents($this->projectDir.'/'.$sourcePath));
        if (!hash_equals((string) ($definition['source']['content_digest'] ?? ''), $sourceDigest)) {
            throw new \RuntimeException('G22_PROFILE_DEFINITION_SOURCE_CHANGED: '.$seat.' source no longer matches its version.');
        }

        return $reference + [
            'source' => $sourcePath,
            'approval_attestation_id' => $approval['attestation_id'],
            'current_attestation_id' => $current['attestation_id'],
        ];
    }

    private function read(string $path): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException('G19_PROFILE_DEFINITION_ABSENT: versioned Guildhall definition is unavailable.');
        }

        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function digestMatches(array $record, string $field): bool
    {
        $digest = $record[$field] ?? null;
        unset($record[$field]);

        return is_string($digest) && hash_equals($digest, 'sha256:'.hash('sha256', CanonicalJson::encode($record)));
    }

    private function sameReference(array $expected, mixed $actual): bool
    {
        return is_array($actual) && CanonicalJson::encode($expected) === CanonicalJson::encode($actual);
    }
}
