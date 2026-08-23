<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Guildhall;

use App\Bootstrap\CanonicalJson;

final readonly class CanonicalGuildhallStaffRegistry
{
    private string $projectDir;
    private string $directory;

    public function __construct(string $projectDir, private ProfileDefinitionRegistry $definitions)
    {
        $this->projectDir = $projectDir;
        $this->directory = $projectDir.'/offices/guildhall/canonical-staff';
    }

    public function current(): array
    {
        $package = $this->read($this->directory.'/package.json');
        if (!$this->digestMatches($package, 'record_digest')
            || 'imperium.guildhall-canonical-staff-package/v1' !== ($package['schema'] ?? null)
            || 'guildhall' !== ($package['steward'] ?? null)
            || 4 !== count($package['members'] ?? [])
        ) {
            throw new \RuntimeException('G30_CANONICAL_STAFF_INVALID: Guildhall canonical staff package is invalid.');
        }
        $expected = [
            'guildhall.guildmaster' => 'guildmaster',
            'guildhall.committee.disciplinary-fit' => 'committee-disciplinary-fit',
            'guildhall.committee.composition' => 'committee-composition',
            'guildhall.committee.boundary-challenge' => 'committee-boundary-challenge',
        ];
        foreach ($package['members'] as $member) {
            $seat = $member['seat'] ?? null;
            if (!is_string($seat) || !isset($expected[$seat])) {
                throw new \RuntimeException('G30_CANONICAL_STAFF_INVALID: unexpected or duplicate Guildhall Seat.');
            }
            $name = $expected[$seat];
            unset($expected[$seat]);
            foreach (['persona', 'profile', 'approval', 'current_active'] as $artifact) {
                $this->verifyFile($member[$artifact] ?? null);
            }
            $persona = $this->read($this->projectDir.'/'.$member['persona']['path']);
            $profile = $this->read($this->projectDir.'/'.$member['profile']['path']);
            $approval = $this->read($this->projectDir.'/'.$member['approval']['path']);
            $current = $this->read($this->projectDir.'/'.$member['current_active']['path']);
            $definition = $this->definitions->current($name, $seat);
            $personaSource = $persona['source'] ?? null;
            $personaSourcePath = is_array($personaSource) ? ($personaSource['path'] ?? null) : null;
            $definitionRef = ['definition_id' => $definition['definition_id'], 'definition_version' => $definition['definition_version'], 'content_digest' => $definition['content_digest']];
            if ('admitted' !== ($persona['admission']['state'] ?? null)
                || 'garrison.constable' !== ($persona['admission']['authority'] ?? null)
                || !is_string($personaSourcePath)
                || !is_file($this->projectDir.'/'.$personaSourcePath)
                || !hash_equals((string) ($personaSource['content_digest'] ?? ''), 'sha256:'.hash_file('sha256', $this->projectDir.'/'.$personaSourcePath))
                || !$this->digestMatches($profile, 'content_digest')
                || !$this->digestMatches($approval, 'record_digest')
                || !$this->digestMatches($current, 'record_digest')
                || $seat !== ($profile['target']['id'] ?? null)
                || ($member['persona']['content_digest'] ?? null) !== ($profile['source_persona']['persona_digest'] ?? null)
                || ($persona['persona_id'] ?? null) !== ($profile['source_persona']['persona_id'] ?? null)
                || ($persona['persona_version'] ?? null) !== ($profile['source_persona']['persona_version'] ?? null)
                || CanonicalJson::encode($definitionRef) !== CanonicalJson::encode($profile['cognitive_payload']['profile_definition'] ?? null)
                || 'approved' !== ($approval['transition']['to'] ?? null)
                || 'imperator-development-root' !== ($approval['actor']['id'] ?? null)
                || 'current_active' !== ($current['transition']['to'] ?? null)
                || 'guildhall.profile-registry' !== ($current['actor']['id'] ?? null)
                || ($approval['attestation_id'] ?? null) !== ($current['transition']['prior_attestation_id'] ?? null)
            ) {
                throw new \RuntimeException('G30_CANONICAL_STAFF_INVALID: '.$seat.' canonical chain is invalid.');
            }
            $ref = ['profile_id' => $profile['profile_id'] ?? null, 'profile_version' => $profile['profile_version'] ?? null, 'content_digest' => $profile['content_digest'] ?? null];
            if (CanonicalJson::encode($ref) !== CanonicalJson::encode($approval['profile_ref'] ?? null)
                || CanonicalJson::encode($ref) !== CanonicalJson::encode($current['profile_ref'] ?? null)
            ) {
                throw new \RuntimeException('G30_CANONICAL_STAFF_INVALID: '.$seat.' lifecycle references another Profile.');
            }
        }
        if ([] !== $expected) {
            throw new \RuntimeException('G30_CANONICAL_STAFF_INVALID: canonical Guildhall Seats are incomplete.');
        }

        return ['package_id' => $package['package_id'], 'package_version' => $package['package_version'], 'record_digest' => $package['record_digest']];
    }

    public function members(): array
    {
        $this->current();
        $package = $this->read($this->directory.'/package.json');

        return array_map(function (array $member): array {
            $persona = $this->read($this->projectDir.'/'.$member['persona']['path']);
            $profile = $this->read($this->projectDir.'/'.$member['profile']['path']);

            return [
                'seat' => $member['seat'],
                'persona' => [
                    'persona_id' => $persona['persona_id'],
                    'persona_version' => $persona['persona_version'],
                    'persona_digest' => $member['persona']['content_digest'],
                    'admission_record' => $persona['admission']['evidence_record'],
                ],
                'profile' => [
                    'profile_id' => $profile['profile_id'],
                    'profile_version' => $profile['profile_version'],
                    'content_digest' => $profile['content_digest'],
                    'approval_attestation' => $member['approval'],
                    'current_active_attestation' => $member['current_active'],
                ],
                'qualification_contract' => $profile['qualification_contract'],
            ];
        }, $package['members']);
    }

    private function verifyFile(mixed $reference): void
    {
        if (!is_array($reference) || !is_string($reference['path'] ?? null) || !is_string($reference['content_digest'] ?? null)) {
            throw new \RuntimeException('G30_CANONICAL_STAFF_INVALID: staff artifact reference is invalid.');
        }
        $path = $this->projectDir.'/'.$reference['path'];
        if (!is_file($path) || !hash_equals($reference['content_digest'], 'sha256:'.hash_file('sha256', $path))) {
            throw new \RuntimeException('G31_CANONICAL_STAFF_CHANGED: canonical staff artifact is absent or changed.');
        }
    }

    private function read(string $path): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException('G29_CANONICAL_STAFF_ABSENT: Guildhall canonical staff package is unavailable.');
        }

        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function digestMatches(array $record, string $field): bool
    {
        $digest = $record[$field] ?? null;
        unset($record[$field]);

        return is_string($digest) && hash_equals($digest, 'sha256:'.hash('sha256', CanonicalJson::encode($record)));
    }
}
