<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Guildhall;

use App\Bootstrap\BootstrapState;
use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;
use App\Imperium\Runtime\Conscription\GenericOfficerSubstrateRegistry;

final readonly class GuildhallSeatBindingService
{
    private string $proceedingDirectory;
    private string $deliveryDirectory;
    private string $occupancyDirectory;

    public function __construct(
        string $projectDir,
        private StateStore $bootstrap,
        private CanonicalGuildhallStaffRegistry $staff,
        private GenericOfficerSubstrateRegistry $substrate,
    ) {
        $this->proceedingDirectory = $projectDir.'/var/imperium/curia/proceedings';
        $this->deliveryDirectory = $projectDir.'/var/imperium/mastermason/qualified-manifestations';
        $this->occupancyDirectory = $projectDir.'/var/imperium/offices/guildhall/occupancy';
    }

    public function bind(string $summonsId): array
    {
        if (!preg_match('/^guildhall-summons-[a-f0-9]{20}$/', $summonsId)) {
            throw new \InvalidArgumentException('M30_GUILDHALL_SUMMONS_INVALID: exact Guildhall summons identity is required.');
        }
        $bootstrap = $this->bootstrap->read();
        if (!is_array($bootstrap) || BootstrapState::CuriaReady->value !== ($bootstrap['state'] ?? null)) {
            throw new \RuntimeException('M31_IMPERIUM_NOT_READY: Guildhall binding requires CURIA_READY.');
        }
        $instanceId = $bootstrap['binding']['instance_id'] ?? null;
        if (!is_string($instanceId) || '' === $instanceId) {
            throw new \RuntimeException('M31_IMPERIUM_NOT_READY: exact Imperium instance binding is absent.');
        }

        $summonsPaths = glob($this->proceedingDirectory.'/*.summons.'.$summonsId.'.json') ?: [];
        if (1 !== count($summonsPaths)) {
            throw new \RuntimeException('M32_GUILDHALL_SUMMONS_ABSENT: one exact recorded Guildhall summons is required.');
        }
        $summons = $this->read($summonsPaths[0]);
        if (!$this->digestMatches($summons)
            || $instanceId !== ($summons['instance_id'] ?? null)
            || 'EXACT_SUMMONS_VALIDATED' !== ($summons['mastermason']['disposition'] ?? null)
            || true !== ($summons['spawning_authority'] ?? null)
            || true === ($summons['recipient_acceptance'] ?? null)
            || true === ($summons['execution_authority'] ?? null)
            || CanonicalJson::encode($this->staff->current()) !== CanonicalJson::encode($summons['canonical_staff_package'] ?? null)
            || CanonicalJson::encode($this->substrate->current()) !== CanonicalJson::encode($summons['generic_officer_substrate'] ?? null)
        ) {
            throw new \RuntimeException('M33_GUILDHALL_SUMMONS_CHANGED: binding source is invalid or stale.');
        }

        $members = [];
        foreach ($this->staff->members() as $member) {
            $members[$member['seat']] = $member;
        }
        $packets = [];
        foreach (glob($this->deliveryDirectory.'/qualified-delivery-*.json') ?: [] as $path) {
            $packet = $this->read($path);
            if ($summonsId === ($packet['source_summons_id'] ?? null)) {
                $seat = $packet['candidate']['target_seat'] ?? '';
                if (isset($packets[$seat])) {
                    throw new \RuntimeException('M34_QUALIFIED_COHORT_INVALID: duplicate packet targets '.$seat.'.');
                }
                $packets[$seat] = $packet;
            }
        }
        $memberSeats = array_keys($members);
        $packetSeats = array_keys($packets);
        sort($memberSeats, SORT_STRING);
        sort($packetSeats, SORT_STRING);
        if (4 !== count($packets) || $memberSeats !== $packetSeats) {
            throw new \RuntimeException('M34_QUALIFIED_COHORT_INVALID: four exact Guildhall packets are required atomically.');
        }

        $bindings = [];
        $packetDigests = [];
        foreach ($members as $seat => $member) {
            $packet = $packets[$seat];
            $qualification = $packet['qualification'] ?? null;
            if (!$this->digestMatches($packet)
                || 'imperium.qualified-manifestation-packet/v1' !== ($packet['schema'] ?? null)
                || ($summons['record_digest'] ?? null) !== ($packet['source_summons_digest'] ?? null)
                || true !== ($packet['commission']['consumed'] ?? null)
                || $instanceId !== ($packet['candidate']['instance_id'] ?? null)
                || 'QUALIFIED_UNBOUND' !== ($packet['candidate']['status'] ?? null)
                || 1 !== ($packet['candidate']['target_occupancy_generation'] ?? null)
                || 'PROFILE_INSTALLED' !== ($packet['candidate']['substrate_instance']['status'] ?? null)
                || CanonicalJson::encode($this->substrate->current()) !== CanonicalJson::encode($packet['candidate']['substrate_instance']['substrate'] ?? null)
                || CanonicalJson::encode($member['persona']) !== CanonicalJson::encode($packet['candidate']['persona'] ?? null)
                || CanonicalJson::encode($member['profile']) !== CanonicalJson::encode($packet['candidate']['profile'] ?? null)
                || !is_array($qualification)
                || 'QUALIFIED' !== ($qualification['disposition'] ?? null)
                || ($packet['candidate']['manifestation_id'] ?? null) !== ($qualification['candidate_id'] ?? null)
                || CanonicalJson::encode($member['qualification_contract']) !== CanonicalJson::encode($qualification['qualification_contract'] ?? null)
                || !hash_equals((string) ($packet['qualification_digest'] ?? ''), hash('sha256', CanonicalJson::encode($qualification)))
                || true !== ($packet['sealed'] ?? null)
                || true === ($packet['seat_binding_authority'] ?? null)
                || true === ($packet['recipient_acceptance'] ?? null)
                || true === ($packet['execution_authority'] ?? null)
            ) {
                throw new \RuntimeException('M35_QUALIFIED_PACKET_INVALID: '.$seat.' packet cannot be bound.');
            }
            $bindings[$seat] = [
                'seat' => $seat,
                'manifestation_id' => $packet['candidate']['manifestation_id'],
                'prior_occupancy_generation' => 0,
                'occupancy_generation' => 1,
                'status' => 'BOUND_PENDING_COMMISSION_ACCEPTANCE',
                'source_delivery_id' => $packet['delivery_id'],
                'source_packet_digest' => $packet['record_digest'],
            ];
            $packetDigests[$seat] = $packet['record_digest'];
        }

        $cohort = [
            'schema' => 'imperium.guildhall-seat-binding-cohort/v1',
            'binding_id' => 'guildhall-binding-'.substr(hash('sha256', CanonicalJson::encode([$instanceId, $summonsId, $packetDigests])), 0, 20),
            'instance_id' => $instanceId,
            'office' => 'guildhall',
            'source_summons_id' => $summonsId,
            'source_summons_digest' => $summons['record_digest'],
            'canonical_staff_package' => $this->staff->current(),
            'bindings' => $bindings,
            'office_status' => 'ACTIVE_AWAITING_COMMISSION_ACCEPTANCE',
            'binding_atomic' => true,
            'seat_binding_authority' => true,
            'seat_binding_disposition' => 'CONSUMED_BY_ATOMIC_BINDING',
            'recipient_acceptance' => false,
            'execution_authority' => false,
        ];

        return $this->persist($cohort);
    }

    private function read(string $path): array
    {
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function digestMatches(array $record): bool
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);

        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }

    private function persist(array $cohort): array
    {
        if (!is_dir($this->occupancyDirectory) && !mkdir($this->occupancyDirectory, 0770, true) && !is_dir($this->occupancyDirectory)) {
            throw new \RuntimeException('Guildhall occupancy directory cannot be created.');
        }
        $cohort['record_digest'] = hash('sha256', CanonicalJson::encode($cohort));
        $path = $this->occupancyDirectory.'/'.$cohort['binding_id'].'.json';
        if (is_file($path)) {
            $existing = $this->read($path);
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($cohort)) {
                throw new \RuntimeException('M36_BINDING_REPLAY_CONFLICT: binding identity is already committed differently.');
            }

            return $existing;
        }
        $existing = glob($this->occupancyDirectory.'/guildhall-binding-*.json') ?: [];
        if ([] !== $existing) {
            throw new \RuntimeException('M37_GUILDHALL_ALREADY_BOUND: Guildhall already has an occupancy cohort.');
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        $json = json_encode($cohort, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
        if (false === file_put_contents($temporary, $json, LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('Guildhall binding cohort cannot be committed atomically.');
        }

        return $cohort;
    }
}
