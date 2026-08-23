<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\BootstrapState;
use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;
use App\Imperium\Runtime\Conscription\GenericOfficerSubstrateRegistry;

final readonly class ArtificerSeatBindingService
{
    private string $deliveryDirectory;
    private string $caseDirectory;
    private string $occupancyDirectory;

    public function __construct(string $projectDir, private StateStore $bootstrap, private CanonicalFoundryStaffRegistry $staff, private GenericOfficerSubstrateRegistry $substrate)
    {
        $this->deliveryDirectory = $projectDir.'/var/imperium/mastermason/qualified-manifestations';
        $this->caseDirectory = $projectDir.'/var/imperium/mastermason/activation-cases';
        $this->occupancyDirectory = $projectDir.'/var/imperium/offices/foundry/occupancy';
    }

    public function bind(string $deliveryId): array
    {
        if (!preg_match('/^qualified-delivery-[a-f0-9]{20}$/', $deliveryId)) throw new \InvalidArgumentException('F50_DELIVERY_INVALID: exact qualified Artificer delivery identity is required.');
        $bootstrap = $this->bootstrap->read();
        if (!is_array($bootstrap) || BootstrapState::CuriaReady->value !== ($bootstrap['state'] ?? null)) throw new \RuntimeException('F51_IMPERIUM_NOT_READY: Artificer binding requires CURIA_READY.');
        $instanceId = $bootstrap['binding']['instance_id'] ?? null;
        if (!is_string($instanceId) || '' === $instanceId) throw new \RuntimeException('F51_IMPERIUM_NOT_READY: exact Imperium instance binding is absent.');
        $packet = $this->read($this->deliveryDirectory.'/'.$deliveryId.'.json', 'F52_DELIVERY_ABSENT');
        $member = $this->staff->member(); $qualification = $packet['qualification'] ?? null;
        if (!$this->digestMatches($packet) || $deliveryId !== ($packet['delivery_id'] ?? null)
            || 'imperium.qualified-manifestation-packet/v1' !== ($packet['schema'] ?? null) || true !== ($packet['commission']['consumed'] ?? null)
            || $instanceId !== ($packet['candidate']['instance_id'] ?? null) || 'foundry.artificer' !== ($packet['candidate']['target_seat'] ?? null)
            || 'QUALIFIED_UNBOUND' !== ($packet['candidate']['status'] ?? null) || 1 !== ($packet['candidate']['target_occupancy_generation'] ?? null)
            || 'PROFILE_INSTALLED' !== ($packet['candidate']['substrate_instance']['status'] ?? null)
            || CanonicalJson::encode($this->substrate->current()) !== CanonicalJson::encode($packet['candidate']['substrate_instance']['substrate'] ?? null)
            || CanonicalJson::encode($member['persona']) !== CanonicalJson::encode($packet['candidate']['persona'] ?? null)
            || CanonicalJson::encode($member['profile']) !== CanonicalJson::encode($packet['candidate']['profile'] ?? null)
            || !is_array($qualification) || 'QUALIFIED' !== ($qualification['disposition'] ?? null)
            || ($packet['candidate']['manifestation_id'] ?? null) !== ($qualification['candidate_id'] ?? null)
            || CanonicalJson::encode($member['qualification_contract']) !== CanonicalJson::encode($qualification['qualification_contract'] ?? null)
            || !hash_equals((string) ($packet['qualification_digest'] ?? ''), hash('sha256', CanonicalJson::encode($qualification)))
            || true !== ($packet['sealed'] ?? null) || true === ($packet['foundry_construction_authority'] ?? null)
            || true === ($packet['seat_binding_authority'] ?? null) || true === ($packet['recipient_acceptance'] ?? null)
            || true === ($packet['execution_authority'] ?? null)) {
            throw new \RuntimeException('F53_QUALIFIED_PACKET_INVALID: exact sealed qualified-unbound Artificer packet is required.');
        }
        $caseId = $packet['source_provisioning_case_id'] ?? null;
        if (!is_string($caseId)) throw new \RuntimeException('F54_PROVISIONING_CHAIN_INVALID: source provisioning case is absent.');
        $case = $this->read($this->caseDirectory.'/'.$caseId.'.json', 'F54_PROVISIONING_CHAIN_INVALID');
        if (!$this->digestMatches($case) || $caseId !== ($case['case_id'] ?? null) || 'foundry.artificer' !== ($case['seat'] ?? null)
            || 'CANONICAL_STAFF_READY' !== ($case['status'] ?? null) || true !== ($case['construction_authority'] ?? null)
            || CanonicalJson::encode($this->staff->current()) !== CanonicalJson::encode($case['canonical_staff_package'] ?? null)) {
            throw new \RuntimeException('F54_PROVISIONING_CHAIN_INVALID: exact authorized Foundry provisioning case is required.');
        }
        return $this->persist([
            'schema' => 'imperium.foundry-artificer-occupancy/v1',
            'binding_id' => 'foundry-artificer-binding-'.substr(hash('sha256', CanonicalJson::encode([$instanceId, $deliveryId, $packet['record_digest'], $case['record_digest'], $member])), 0, 20),
            'instance_id' => $instanceId, 'office' => 'foundry', 'seat' => 'foundry.artificer',
            'manifestation_id' => $packet['candidate']['manifestation_id'], 'prior_occupancy_generation' => 0, 'occupancy_generation' => 1,
            'source_delivery_id' => $deliveryId, 'source_packet_digest' => $packet['record_digest'],
            'source_provisioning_case_id' => $caseId, 'source_provisioning_case_digest' => $case['record_digest'],
            'source_activation_demand_id' => $case['activation_demand_id'] ?? null, 'canonical_foundry_staff_package' => $this->staff->current(),
            'status' => 'ACTIVE', 'binding_atomic' => true, 'seat_binding_authority' => true, 'seat_binding_disposition' => 'CONSUMED_BY_ATOMIC_BINDING',
            'foundry_construction_authority' => true, 'construction_authority_scope' => 'exact demands authorized through the source Foundry activation chain only',
            'recipient_acceptance' => false, 'execution_authority' => false,
        ]);
    }

    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function digestMatches(array $record): bool { $digest = $record['record_digest'] ?? null; unset($record['record_digest']); return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record))); }
    private function persist(array $occupancy): array
    {
        if (!is_dir($this->occupancyDirectory) && !mkdir($this->occupancyDirectory, 0770, true) && !is_dir($this->occupancyDirectory)) throw new \RuntimeException('Foundry occupancy directory cannot be created.');
        $occupancy['record_digest'] = hash('sha256', CanonicalJson::encode($occupancy)); $path = $this->occupancyDirectory.'/'.$occupancy['binding_id'].'.json';
        if (is_file($path)) { $existing = $this->read($path, 'F55_BINDING_ABSENT'); if (CanonicalJson::encode($existing) !== CanonicalJson::encode($occupancy)) throw new \RuntimeException('F56_BINDING_REPLAY_CONFLICT'); return $existing; }
        if ([] !== (glob($this->occupancyDirectory.'/foundry-artificer-binding-*.json') ?: [])) throw new \RuntimeException('F57_ARTIFICER_ALREADY_BOUND: Artificer Seat is already occupied.');
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($occupancy, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) { @unlink($temporary); throw new \RuntimeException('Artificer occupancy cannot be committed atomically.'); }
        return $occupancy;
    }
}
