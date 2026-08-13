<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Authorship;

use App\Bootstrap\CanonicalJson;

final readonly class AuthorshipOfficeProvisioningService
{
    private string $demandDirectory; private string $caseDirectory;
    public function __construct(string $projectDir, private CanonicalAuthorshipStaffRegistry $staff) { $this->demandDirectory = $projectDir.'/var/imperium/mastermason/spawning-requests'; $this->caseDirectory = $projectDir.'/var/imperium/mastermason/activation-cases'; }

    public function open(string $office, string $demandId): array
    {
        if (!preg_match('/^'.$office.'-activation-[a-f0-9]{20}$/', $demandId)) throw new \InvalidArgumentException('A30_ACTIVATION_DEMAND_INVALID');
        $demand = $this->read($this->demandDirectory.'/'.$demandId.'.json', 'A31_ACTIVATION_DEMAND_ABSENT'); $package = $this->staff->current($office);
        if (!$this->digestMatches($demand) || $office !== ($demand['office'] ?? null) || 'CANONICAL_STAFF_ARTIFACTS_REQUIRED' !== ($demand['status'] ?? null)
            || $package['seat'] !== ($demand['required_seats'][0]['seat'] ?? null) || true !== ($demand['authorship_authority'] ?? null)
            || true === ($demand['authorship_authority_exercisable'] ?? null) || true === ($demand['spawning_authority'] ?? null)
            || true === ($demand['seat_binding_authority'] ?? null) || true === ($demand['recipient_acceptance'] ?? null) || true === ($demand['execution_authority'] ?? null)) throw new \RuntimeException('A32_ACTIVATION_DEMAND_INVALID');
        $caseId = $office.'-provisioning-'.substr(hash('sha256', CanonicalJson::encode([$office, $demand['instance_id'], $package])), 0, 20);
        return $this->persist($caseId, ['schema' => 'imperium.office-provisioning-case/v1', 'case_id' => $caseId, 'instance_id' => $demand['instance_id'], 'office' => $office,
            'canonical_staff_package' => $package, 'seat' => $package['seat'], 'status' => 'CANONICAL_STAFF_READY', 'authorship_authority' => true, 'authorship_authority_exercisable' => false,
            'mission_persona_selection_required' => false, 'per_mission_profile_derivation_required' => false, 'subordinate_staff_resolution_pending' => true,
            'commission_authority' => false, 'spawning_authority' => false, 'seat_binding_authority' => false, 'recipient_acceptance' => false, 'execution_authority' => false]);
    }
    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function digestMatches(array $r): bool { $d = $r['record_digest'] ?? null; unset($r['record_digest']); return is_string($d) && hash_equals($d, hash('sha256', CanonicalJson::encode($r))); }
    private function persist(string $id, array $record): array { if (!is_dir($this->caseDirectory) && !mkdir($this->caseDirectory, 0770, true) && !is_dir($this->caseDirectory)) throw new \RuntimeException('Authorship provisioning directory cannot be created.'); $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); $path = $this->caseDirectory.'/'.$id.'.json'; if (is_file($path)) { $old = $this->read($path, 'A33_PROVISIONING_ABSENT'); if (CanonicalJson::encode($old) !== CanonicalJson::encode($record)) throw new \RuntimeException('A34_PROVISIONING_REPLAY_CONFLICT'); return $old; } $tmp = $path.'.tmp.'.bin2hex(random_bytes(6)); if (false === file_put_contents($tmp, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($tmp, $path)) { @unlink($tmp); throw new \RuntimeException('Authorship provisioning case cannot be committed atomically.'); } return $record; }
}
