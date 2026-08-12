<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Conscription\GenericOfficerSubstrateRegistry;

final readonly class ArtificerConstructionCommissionService
{
    private string $caseDirectory;
    private string $conscriptionInbox;

    public function __construct(string $projectDir, private CanonicalFoundryStaffRegistry $staff, private GenericOfficerSubstrateRegistry $substrate)
    {
        $this->caseDirectory = $projectDir.'/var/imperium/mastermason/activation-cases';
        $this->conscriptionInbox = $projectDir.'/var/imperium/offices/conscription/inbox';
    }

    public function issue(string $caseId): array
    {
        if (!preg_match('/^foundry-provisioning-[a-f0-9]{20}$/', $caseId)) throw new \InvalidArgumentException('F30_PROVISIONING_CASE_INVALID: exact Foundry provisioning case is required.');
        $case = $this->read($this->caseDirectory.'/'.$caseId.'.json', 'F31_PROVISIONING_CASE_ABSENT');
        $member = $this->staff->member();
        if (!$this->digestMatches($case) || $caseId !== ($case['case_id'] ?? null) || 'CANONICAL_STAFF_READY' !== ($case['status'] ?? null)
            || 'foundry.artificer' !== ($case['seat'] ?? null) || true !== ($case['construction_authority'] ?? null)
            || true === ($case['commission_authority'] ?? null) || true === ($case['spawning_authority'] ?? null)
            || true === ($case['seat_binding_authority'] ?? null) || true === ($case['recipient_acceptance'] ?? null) || true === ($case['execution_authority'] ?? null)
            || CanonicalJson::encode($this->staff->current()) !== CanonicalJson::encode($case['canonical_staff_package'] ?? null)) {
            throw new \RuntimeException('F32_PROVISIONING_CASE_INVALID: exact ready non-authorizing Foundry case is required.');
        }
        $substrate = $this->substrate->current();
        $commissionId = 'artificer-construction-'.substr(hash('sha256', CanonicalJson::encode([$caseId, $case['record_digest'], $member, $substrate])), 0, 20);
        return $this->persist([
            'schema' => 'imperium.construction-commission/v1', 'commission_id' => $commissionId, 'issuer' => 'mastermason',
            'source_provisioning_case_id' => $caseId, 'source_provisioning_case_digest' => $case['record_digest'],
            'source_activation_demand_id' => $case['activation_demand_id'], 'office' => 'foundry', 'target_seat' => 'foundry.artificer',
            'persona' => $member['persona'], 'profile' => $member['profile'], 'qualification_contract' => $member['qualification_contract'], 'substrate' => $substrate,
            'status' => 'ISSUED_PENDING_CONSCRIPTION', 'commission_authority' => true, 'spawning_authority' => true,
            'authority_scope' => 'instantiate and qualify one canonical Artificer manifestation for the exact vacant Foundry Seat',
            'foundry_construction_authority' => false, 'seat_binding_authority' => false, 'recipient_acceptance' => false, 'execution_authority' => false,
        ]);
    }

    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function digestMatches(array $record): bool { $digest = $record['record_digest'] ?? null; unset($record['record_digest']); return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record))); }
    private function persist(array $commission): array { if (!is_dir($this->conscriptionInbox) && !mkdir($this->conscriptionInbox, 0770, true) && !is_dir($this->conscriptionInbox)) throw new \RuntimeException('Conscription inbox cannot be created.'); $commission['record_digest'] = hash('sha256', CanonicalJson::encode($commission)); $path = $this->conscriptionInbox.'/'.$commission['commission_id'].'.json'; if (is_file($path)) { $old = $this->read($path, 'F33_COMMISSION_ABSENT'); if (CanonicalJson::encode($old) !== CanonicalJson::encode($commission)) throw new \RuntimeException('F34_COMMISSION_REPLAY_CONFLICT'); return $old; } $tmp = $path.'.tmp.'.bin2hex(random_bytes(6)); if (false === file_put_contents($tmp, json_encode($commission, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($tmp, $path)) { @unlink($tmp); throw new \RuntimeException('Artificer commission cannot be committed atomically.'); } return $commission; }
}
