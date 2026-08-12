<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Garrison;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Conscription\GenericOfficerSubstrateRegistry;

final readonly class ConstableConstructionCommissionService
{
    private string $cases;
    private string $conscriptionInbox;

    public function __construct(string $projectDir, private CanonicalConstableRegistry $constable, private GenericOfficerSubstrateRegistry $substrate)
    {
        $this->cases = $projectDir.'/var/imperium/mastermason/activation-cases';
        $this->conscriptionInbox = $projectDir.'/var/imperium/offices/conscription/inbox';
    }

    public function issue(string $caseId): array
    {
        if (!preg_match('/^constable-provisioning-[a-f0-9]{20}$/', $caseId)) throw new \InvalidArgumentException('GA40_PROVISIONING_CASE_INVALID: exact Constable provisioning case identity is required.');
        $case = $this->read($this->cases.'/'.$caseId.'.json', 'GA41_PROVISIONING_CASE_ABSENT');
        $member = $this->constable->member();
        if (!$this->digestMatches($case) || $caseId !== ($case['case_id'] ?? null) || 'CANONICAL_CONSTABLE_READY' !== ($case['status'] ?? null)
            || 'garrison.constable' !== ($case['target_seat'] ?? null) || true === ($case['spawning_authority'] ?? null)
            || true === ($case['seat_binding_authority'] ?? null) || true === ($case['inventory_response_authority'] ?? null) || true === ($case['execution_authority'] ?? null)
            || CanonicalJson::encode($this->constable->current()) !== CanonicalJson::encode($case['canonical_constable_package'] ?? null)
            || CanonicalJson::encode($member) !== CanonicalJson::encode($case['member'] ?? null)) {
            throw new \RuntimeException('GA42_PROVISIONING_CASE_INVALID: exact ready non-authorizing Constable case is required.');
        }
        $substrate = $this->substrate->current();
        $commissionId = 'constable-construction-'.substr(hash('sha256', CanonicalJson::encode([$caseId, $case['record_digest'], $member, $substrate])), 0, 20);
        return $this->persist([
            'schema' => 'imperium.construction-commission/v1', 'commission_id' => $commissionId, 'issuer' => 'mastermason',
            'source_provisioning_case_id' => $caseId, 'source_provisioning_case_digest' => $case['record_digest'],
            'source_inquiry_id' => $case['source_inquiry_id'], 'instance_id' => $case['instance_id'], 'office' => 'garrison',
            'target_seat' => 'garrison.constable', 'persona' => $member['persona'], 'profile' => $member['profile'],
            'qualification_contract' => $member['qualification_contract'], 'substrate' => $substrate,
            'status' => 'ISSUED_PENDING_CONSCRIPTION', 'spawning_authority' => true,
            'authority_scope' => 'instantiate and qualify one canonical Constable manifestation for the exact vacant Garrison Seat',
            'seat_binding_authority' => false, 'inventory_response_authority' => false, 'execution_authority' => false,
        ]);
    }

    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function digestMatches(array $record): bool { $digest = $record['record_digest'] ?? null; unset($record['record_digest']); return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record))); }
    private function persist(array $commission): array
    {
        if (!is_dir($this->conscriptionInbox) && !mkdir($this->conscriptionInbox, 0770, true) && !is_dir($this->conscriptionInbox)) throw new \RuntimeException('Conscription inbox cannot be created.');
        $commission['record_digest'] = hash('sha256', CanonicalJson::encode($commission));
        $path = $this->conscriptionInbox.'/'.$commission['commission_id'].'.json';
        if (is_file($path)) { $existing = $this->read($path, 'GA43_CONSTRUCTION_COMMISSION_ABSENT'); if (CanonicalJson::encode($existing) !== CanonicalJson::encode($commission)) throw new \RuntimeException('GA44_CONSTRUCTION_COMMISSION_REPLAY_CONFLICT'); return $existing; }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($commission, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) { @unlink($temporary); throw new \RuntimeException('Constable construction commission cannot be committed atomically.'); }
        return $commission;
    }
}
