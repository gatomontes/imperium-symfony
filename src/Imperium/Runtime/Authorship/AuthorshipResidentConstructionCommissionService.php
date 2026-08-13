<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Authorship;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Conscription\GenericOfficerSubstrateRegistry;

final readonly class AuthorshipResidentConstructionCommissionService
{
    private string $caseDirectory; private string $inbox;
    public function __construct(string $projectDir, private CanonicalAuthorshipStaffRegistry $staff, private GenericOfficerSubstrateRegistry $substrate) { $this->caseDirectory = $projectDir.'/var/imperium/mastermason/activation-cases'; $this->inbox = $projectDir.'/var/imperium/offices/conscription/inbox'; }
    public function issue(string $office, string $caseId): array
    {
        [$role, $seat, $subordinate] = match ($office) { 'hagiography' => ['sanctographer', 'hagiography.sanctographer', 'Chronicler'], 'studium' => ['chancellor', 'studium.chancellor', 'Notary'], default => throw new \InvalidArgumentException('A19_AUTHORSHIP_OFFICE_INVALID') };
        if (!preg_match('/^'.$office.'-provisioning-[a-f0-9]{20}$/', $caseId)) throw new \InvalidArgumentException('A30_PROVISIONING_CASE_INVALID');
        $case = $this->read($this->caseDirectory.'/'.$caseId.'.json', 'A31_PROVISIONING_CASE_ABSENT');
        if (!$this->digestMatches($case) || $caseId !== ($case['case_id'] ?? null) || $office !== ($case['office'] ?? null) || $seat !== ($case['seat'] ?? null) || 'CANONICAL_STAFF_READY' !== ($case['status'] ?? null)
            || true !== ($case['authorship_authority'] ?? null) || true === ($case['authorship_authority_exercisable'] ?? null) || true === ($case['commission_authority'] ?? null) || true === ($case['spawning_authority'] ?? null)
            || true === ($case['seat_binding_authority'] ?? null) || true === ($case['recipient_acceptance'] ?? null) || true === ($case['execution_authority'] ?? null)
            || CanonicalJson::encode($this->staff->current($office)) !== CanonicalJson::encode($case['canonical_staff_package'] ?? null)) throw new \RuntimeException('A32_PROVISIONING_CASE_INVALID');
        $member = $this->staff->member($office); $substrate = $this->substrate->current();
        $id = $role.'-construction-'.substr(hash('sha256', CanonicalJson::encode([$caseId, $case['record_digest'], $member, $substrate])), 0, 20);
        return $this->persist(['schema' => 'imperium.construction-commission/v1', 'commission_id' => $id, 'issuer' => 'mastermason', 'source_provisioning_case_id' => $caseId, 'source_provisioning_case_digest' => $case['record_digest'], 'office' => $office, 'target_seat' => $seat,
            'persona' => $member['persona'], 'profile' => $member['profile'], 'qualification_contract' => $member['qualification_contract'], 'substrate' => $substrate, 'status' => 'ISSUED_PENDING_CONSCRIPTION', 'commission_authority' => true, 'spawning_authority' => true,
            'authority_scope' => 'instantiate and qualify one canonical '.ucfirst($role).' manifestation for the exact vacant resident Seat', 'authorship_authority' => false, 'subordinate_staff_resolution_authority' => false, 'subordinate_staff_class' => $subordinate, 'seat_binding_authority' => false, 'recipient_acceptance' => false, 'execution_authority' => false]);
    }
    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function digestMatches(array $record): bool { $digest = $record['record_digest'] ?? null; unset($record['record_digest']); return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record))); }
    private function persist(array $record): array { if (!is_dir($this->inbox) && !mkdir($this->inbox, 0770, true) && !is_dir($this->inbox)) throw new \RuntimeException('Conscription inbox cannot be created.'); $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); $path = $this->inbox.'/'.$record['commission_id'].'.json'; if (is_file($path)) { $old = $this->read($path, 'A33_COMMISSION_ABSENT'); if (CanonicalJson::encode($old) !== CanonicalJson::encode($record)) throw new \RuntimeException('A34_COMMISSION_REPLAY_CONFLICT'); return $old; } $tmp = $path.'.tmp.'.bin2hex(random_bytes(6)); if (false === file_put_contents($tmp, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($tmp, $path)) { @unlink($tmp); throw new \RuntimeException('Authorship resident commission cannot be committed atomically.'); } return $record; }
}
