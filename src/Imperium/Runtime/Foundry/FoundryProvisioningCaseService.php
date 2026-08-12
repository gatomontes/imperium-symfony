<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;

final readonly class FoundryProvisioningCaseService
{
    public function __construct(private string $projectDir, private CanonicalFoundryStaffRegistry $staff) {}

    public function open(string $demandId): array
    {
        if (!preg_match('/^foundry-activation-[a-f0-9]{20}$/', $demandId)) throw new \InvalidArgumentException('M70_ACTIVATION_DEMAND_INVALID');
        $demand = $this->read($this->projectDir.'/var/imperium/mastermason/spawning-requests/'.$demandId.'.json', 'M71_ACTIVATION_DEMAND_ABSENT');
        if (!$this->digestMatches($demand) || 'foundry' !== ($demand['office'] ?? null) || 'CANONICAL_STAFF_ARTIFACTS_REQUIRED' !== ($demand['status'] ?? null)
            || true !== ($demand['construction_authority'] ?? null) || true === ($demand['spawning_authority'] ?? null) || true === ($demand['recipient_acceptance'] ?? null)
            || true === ($demand['execution_authority'] ?? null) || 'foundry.artificer' !== ($demand['required_seats'][0]['seat'] ?? null)) throw new \RuntimeException('M72_ACTIVATION_DEMAND_INVALID');
        $package = $this->staff->current();
        $caseId = 'foundry-provisioning-'.substr(hash('sha256', CanonicalJson::encode([$demandId, $demand['record_digest'], $package])), 0, 20);
        return $this->persist($caseId, ['schema' => 'imperium.office-provisioning-case/v1', 'case_id' => $caseId, 'activation_demand_id' => $demandId,
            'activation_demand_digest' => $demand['record_digest'], 'office' => 'foundry', 'coordinator' => 'mastermason', 'canonical_staff_package' => $package,
            'seat' => 'foundry.artificer', 'status' => 'CANONICAL_STAFF_READY', 'construction_authority' => true,
            'mission_persona_selection_required' => false, 'per_mission_profile_derivation_required' => false, 'commission_authority' => false,
            'spawning_authority' => false, 'seat_binding_authority' => false, 'recipient_acceptance' => false, 'execution_authority' => false]);
    }

    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function digestMatches(array $r): bool { $d = $r['record_digest'] ?? null; unset($r['record_digest']); return is_string($d) && hash_equals($d, hash('sha256', CanonicalJson::encode($r))); }
    private function persist(string $id, array $record): array { $dir = $this->projectDir.'/var/imperium/mastermason/activation-cases'; if (!is_dir($dir) && !mkdir($dir, 0770, true) && !is_dir($dir)) throw new \RuntimeException('Foundry provisioning directory cannot be created.'); $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); $path = $dir.'/'.$id.'.json'; if (is_file($path)) { $old = $this->read($path, 'M73_PROVISIONING_ABSENT'); if (CanonicalJson::encode($old) !== CanonicalJson::encode($record)) throw new \RuntimeException('M74_PROVISIONING_REPLAY_CONFLICT'); return $old; } $tmp = $path.'.tmp.'.bin2hex(random_bytes(6)); if (false === file_put_contents($tmp, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($tmp, $path)) { @unlink($tmp); throw new \RuntimeException('Foundry provisioning case cannot be committed atomically.'); } return $record; }
}
