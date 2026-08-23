<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Authorship;

use App\Bootstrap\CanonicalJson;

final readonly class CanonicalAuthorshipStaffRegistry
{
    public function __construct(private string $projectDir) {}

    public function current(string $office): array
    {
        [$role, $seat] = $this->configuration($office); $directory = $this->projectDir.'/offices/'.$office.'/canonical-staff';
        $package = $this->read($directory.'/package.json');
        if (!$this->digestMatches($package, 'record_digest') || 'imperium.authorship-canonical-staff-package/v1' !== ($package['schema'] ?? null)
            || $office !== ($package['steward'] ?? null) || $seat !== ($package['seat'] ?? null) || $role !== ($package['role'] ?? null)) throw new \RuntimeException('A20_CANONICAL_STAFF_INVALID');
        $records = [];
        foreach (($package['artifacts'] ?? []) as $name => $ref) { $path = $this->projectDir.'/'.($ref['path'] ?? ''); if (!is_file($path) || !hash_equals((string) ($ref['content_digest'] ?? ''), 'sha256:'.hash_file('sha256', $path))) throw new \RuntimeException('A21_CANONICAL_STAFF_CHANGED'); $records[$name] = $this->read($path); }
        $persona = $records['persona'] ?? []; $profile = $records['profile'] ?? []; $approval = $records['profile-approved'] ?? []; $current = $records['profile-current-active'] ?? [];
        $ref = ['profile_id' => $profile['profile_id'] ?? null, 'profile_version' => $profile['profile_version'] ?? null, 'content_digest' => $profile['content_digest'] ?? null];
        if ('admitted' !== ($persona['admission']['state'] ?? null) || 'garrison.constable' !== ($persona['admission']['authority'] ?? null)
            || !$this->digestMatches($profile, 'content_digest') || !$this->digestMatches($approval, 'record_digest') || !$this->digestMatches($current, 'record_digest')
            || $seat !== ($profile['target']['id'] ?? null) || CanonicalJson::encode($ref) !== CanonicalJson::encode($approval['profile_ref'] ?? null)
            || CanonicalJson::encode($ref) !== CanonicalJson::encode($current['profile_ref'] ?? null) || 'approved' !== ($approval['transition']['to'] ?? null)
            || 'imperator-development-root' !== ($approval['actor']['id'] ?? null) || 'current_active' !== ($current['transition']['to'] ?? null)
            || $office.'.profile-registry' !== ($current['actor']['id'] ?? null)) throw new \RuntimeException('A20_CANONICAL_STAFF_INVALID');
        return ['package_id' => $package['package_id'], 'package_version' => $package['package_version'], 'record_digest' => $package['record_digest'], 'seat' => $seat, 'role' => $role];
    }

    public function member(string $office): array
    {
        [, $seat] = $this->configuration($office); $this->current($office);
        $package = $this->read($this->projectDir.'/offices/'.$office.'/canonical-staff/package.json');
        $persona = $this->read($this->projectDir.'/'.$package['artifacts']['persona']['path']);
        $profile = $this->read($this->projectDir.'/'.$package['artifacts']['profile']['path']);
        return ['seat' => $seat,
            'persona' => ['persona_id' => $persona['persona_id'], 'persona_version' => $persona['persona_version'], 'persona_digest' => $package['artifacts']['persona']['content_digest'], 'admission_record' => $persona['admission']['evidence_record']],
            'profile' => ['profile_id' => $profile['profile_id'], 'profile_version' => $profile['profile_version'], 'content_digest' => $profile['content_digest'], 'approval_attestation' => $package['artifacts']['profile-approved'], 'current_active_attestation' => $package['artifacts']['profile-current-active']],
            'qualification_contract' => $profile['qualification_contract']];
    }

    private function configuration(string $office): array { return match ($office) { 'hagiography' => ['sanctographer', 'hagiography.sanctographer'], 'studium' => ['chancellor', 'studium.chancellor'], default => throw new \InvalidArgumentException('A19_AUTHORSHIP_OFFICE_INVALID') }; }
    private function read(string $path): array { if (!is_file($path)) throw new \RuntimeException('A18_CANONICAL_STAFF_ABSENT'); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function digestMatches(array $record, string $field): bool { $digest = $record[$field] ?? null; unset($record[$field]); return is_string($digest) && hash_equals($digest, 'sha256:'.hash('sha256', CanonicalJson::encode($record))); }
}
