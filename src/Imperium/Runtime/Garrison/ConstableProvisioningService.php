<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Garrison;

use App\Bootstrap\CanonicalJson;

final readonly class ConstableProvisioningService
{
    private string $inbox;
    private string $cases;

    public function __construct(string $projectDir, private CanonicalConstableRegistry $constable)
    {
        $this->inbox = $projectDir.'/var/imperium/offices/garrison/inbox';
        $this->cases = $projectDir.'/var/imperium/mastermason/activation-cases';
    }

    public function open(string $inquiryId): array
    {
        if (!preg_match('/^garrison-inquiry-[a-f0-9]{20}$/', $inquiryId)) throw new \InvalidArgumentException('GA30_INQUIRY_INVALID: exact Garrison inquiry identity is required.');
        $inquiry = $this->read($this->inbox.'/'.$inquiryId.'.json');
        if (!$this->digestMatches($inquiry)
            || 'CONSTABLE_ACTIVATION_REQUIRED' !== ($inquiry['status'] ?? null)
            || null !== ($inquiry['constable_occupancy'] ?? null)
            || true === ($inquiry['authoritative_inventory_response'] ?? null)
            || true === ($inquiry['execution_authority'] ?? null)
        ) throw new \RuntimeException('GA31_INQUIRY_INVALID: exact vacancy-blocked inquiry is required.');
        $package = $this->constable->current();
        $member = $this->constable->member();
        $case = [
            'schema' => 'imperium.garrison-constable-provisioning-case/v1',
            'case_id' => 'constable-provisioning-'.substr(hash('sha256', CanonicalJson::encode([$inquiryId, $inquiry['record_digest'], $package, $member])), 0, 20),
            'instance_id' => $inquiry['instance_id'],
            'source_inquiry_id' => $inquiryId,
            'source_inquiry_digest' => $inquiry['record_digest'],
            'coordinator' => 'mastermason',
            'target_seat' => 'garrison.constable',
            'canonical_constable_package' => $package,
            'member' => $member,
            'status' => 'CANONICAL_CONSTABLE_READY',
            'mission_persona_selection_required' => false,
            'per_mission_profile_derivation_required' => false,
            'spawning_authority' => false,
            'seat_binding_authority' => false,
            'inventory_response_authority' => false,
            'execution_authority' => false,
        ];
        return $this->persist($case);
    }

    private function read(string $path): array { if (!is_file($path)) throw new \RuntimeException('GA29_INQUIRY_ABSENT'); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function digestMatches(array $record): bool { $digest = $record['record_digest'] ?? null; unset($record['record_digest']); return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record))); }
    private function persist(array $case): array
    {
        if (!is_dir($this->cases) && !mkdir($this->cases, 0770, true) && !is_dir($this->cases)) throw new \RuntimeException('MasterMason activation-case directory cannot be created.');
        $case['record_digest'] = hash('sha256', CanonicalJson::encode($case));
        $path = $this->cases.'/'.$case['case_id'].'.json';
        if (is_file($path)) { $existing = $this->read($path); if (CanonicalJson::encode($existing) !== CanonicalJson::encode($case)) throw new \RuntimeException('GA32_PROVISIONING_REPLAY_CONFLICT'); return $existing; }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($case, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) { @unlink($temporary); throw new \RuntimeException('Constable provisioning case cannot be committed atomically.'); }
        return $case;
    }
}
