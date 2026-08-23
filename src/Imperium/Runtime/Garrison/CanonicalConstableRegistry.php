<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Garrison;

use App\Bootstrap\CanonicalJson;

final readonly class CanonicalConstableRegistry
{
    private string $root;
    private string $directory;

    public function __construct(string $projectDir)
    {
        $this->root = $projectDir;
        $this->directory = $projectDir.'/offices/garrison/canonical-staff';
    }

    public function current(): array
    {
        $package = $this->read($this->directory.'/package.json');
        if (!$this->digestMatches($package, 'record_digest')
            || 'imperium.garrison-canonical-constable-package/v1' !== ($package['schema'] ?? null)
            || 'garrison.constable' !== ($package['seat'] ?? null)
            || 'garrison' !== ($package['steward'] ?? null)
        ) {
            throw new \RuntimeException('GA20_CONSTABLE_PACKAGE_INVALID: canonical Constable package is invalid.');
        }
        foreach (['persona', 'profile', 'approval', 'current_active'] as $name) {
            $this->verifyFile($package[$name] ?? null);
        }
        $persona = $this->read($this->root.'/'.$package['persona']['path']);
        $profile = $this->read($this->root.'/'.$package['profile']['path']);
        $approval = $this->read($this->root.'/'.$package['approval']['path']);
        $current = $this->read($this->root.'/'.$package['current_active']['path']);
        $profileRef = ['profile_id' => $profile['profile_id'] ?? null, 'profile_version' => $profile['profile_version'] ?? null, 'content_digest' => $profile['content_digest'] ?? null];
        if ('admitted' !== ($persona['admission']['state'] ?? null)
            || 'garrison.constable' !== ($profile['target']['id'] ?? null)
            || !$this->digestMatches($profile, 'content_digest')
            || !$this->digestMatches($approval, 'record_digest')
            || !$this->digestMatches($current, 'record_digest')
            || CanonicalJson::encode($profileRef) !== CanonicalJson::encode($approval['profile_ref'] ?? null)
            || CanonicalJson::encode($profileRef) !== CanonicalJson::encode($current['profile_ref'] ?? null)
            || 'approved' !== ($approval['transition']['to'] ?? null)
            || 'current_active' !== ($current['transition']['to'] ?? null)
            || ($approval['attestation_id'] ?? null) !== ($current['transition']['prior_attestation_id'] ?? null)
            || CanonicalJson::encode($package['qualification_contract'] ?? null) !== CanonicalJson::encode($profile['qualification_contract'] ?? null)
        ) {
            throw new \RuntimeException('GA20_CONSTABLE_PACKAGE_INVALID: canonical Constable lifecycle chain is invalid.');
        }

        return ['package_id' => $package['package_id'], 'package_version' => $package['package_version'], 'record_digest' => $package['record_digest']];
    }

    public function member(): array
    {
        $this->current();
        $package = $this->read($this->directory.'/package.json');
        $persona = $this->read($this->root.'/'.$package['persona']['path']);
        $profile = $this->read($this->root.'/'.$package['profile']['path']);
        return [
            'seat' => 'garrison.constable',
            'persona' => ['persona_id' => $persona['persona_id'], 'persona_version' => $persona['persona_version'], 'persona_digest' => $package['persona']['content_digest'], 'admission_record' => $persona['admission']['evidence_record']],
            'profile' => ['profile_id' => $profile['profile_id'], 'profile_version' => $profile['profile_version'], 'content_digest' => $profile['content_digest'], 'approval_attestation' => $package['approval'], 'current_active_attestation' => $package['current_active']],
            'qualification_contract' => $package['qualification_contract'],
        ];
    }

    private function verifyFile(mixed $reference): void
    {
        if (!is_array($reference) || !is_string($reference['path'] ?? null) || !is_string($reference['content_digest'] ?? null)) {
            throw new \RuntimeException('GA20_CONSTABLE_PACKAGE_INVALID: artifact reference is invalid.');
        }
        $path = $this->root.'/'.$reference['path'];
        if (!is_file($path) || !hash_equals($reference['content_digest'], 'sha256:'.hash_file('sha256', $path))) {
            throw new \RuntimeException('GA21_CONSTABLE_ARTIFACT_CHANGED: canonical Constable artifact is absent or changed.');
        }
    }

    private function read(string $path): array
    {
        if (!is_file($path)) throw new \RuntimeException('GA19_CONSTABLE_PACKAGE_ABSENT: canonical Constable package is unavailable.');
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function digestMatches(array $record, string $field): bool
    {
        $digest = $record[$field] ?? null;
        unset($record[$field]);
        return is_string($digest) && hash_equals($digest, 'sha256:'.hash('sha256', CanonicalJson::encode($record)));
    }
}
