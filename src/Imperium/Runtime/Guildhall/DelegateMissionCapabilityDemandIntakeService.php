<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Guildhall;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Identity\OfficerClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionCapabilityDemandIntakeService
{
    private string $demands;
    private string $occupancy;
    private string $dispositions;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->demands = $root.'/var/imperium/offices/curia/delegate-mission-capability-demands';
        $this->occupancy = $root.'/var/imperium/offices/guildhall/occupancy';
        $this->dispositions = $root.'/var/imperium/offices/guildhall/delegate-mission-capability-demand-intake-dispositions';
    }

    public function decide(string $demandId, string $bindingId, string $disposition, string $rationale, \DateTimeImmutable $decidedAt): array
    {
        if (!preg_match('/^delegate-mission-capability-demand-[a-f0-9]{20}$/', $demandId)) {
            throw new \InvalidArgumentException('G490_DELEGATE_MISSION_CAPABILITY_DEMAND_ID_INVALID');
        }
        if (!preg_match('/^guildhall-binding-[a-f0-9]{20}$/', $bindingId)) {
            throw new \InvalidArgumentException('G491_GUILDMASTER_BINDING_ID_INVALID');
        }
        if (!in_array($disposition, ['ACCEPTED', 'REFUSED'], true) || '' === trim($rationale)) {
            throw new \InvalidArgumentException('G492_DELEGATE_MISSION_INTAKE_DECISION_INVALID');
        }

        $demand = $this->read($this->demands.'/'.$demandId.'.json', 'G493_DELEGATE_MISSION_CAPABILITY_DEMAND_ABSENT');
        $binding = $this->read($this->occupancy.'/'.$bindingId.'.json', 'G494_GUILDMASTER_OCCUPANCY_ABSENT');
        $guildmaster = $binding['bindings']['guildhall.guildmaster'] ?? null;
        $this->validate($demandId, $demand, $bindingId, $binding, $guildmaster);
        $this->assertSoleCurrentGuildmaster($bindingId, $demand['instance_id']);

        foreach (glob($this->dispositions.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'G497_DELEGATE_MISSION_INTAKE_CONFLICT');
            if (($prior['source_demand']['id'] ?? null) === $demandId) {
                if (($prior['source_demand']['digest'] ?? null) !== $demand['record_digest']
                    || ($prior['disposition'] ?? null) !== $disposition
                    || ($prior['rationale'] ?? null) !== trim($rationale)
                    || ($prior['actor']['binding_id'] ?? null) !== $bindingId) {
                    throw new \RuntimeException('G497_DELEGATE_MISSION_INTAKE_CONFLICT');
                }

                return $prior;
            }
        }

        $accepted = 'ACCEPTED' === $disposition;
        $actor = [
            'office' => 'guildhall',
            'seat' => 'guildhall.guildmaster',
            'officer_class' => OfficerClass::Legate->value,
            'binding_id' => $bindingId,
            'binding_digest' => $binding['record_digest'],
            'manifestation_id' => $guildmaster['manifestation_id'],
            'occupancy_generation' => $guildmaster['occupancy_generation'],
        ];
        $id = 'delegate-mission-demand-intake-disposition-'.substr(hash('sha256', CanonicalJson::encode([
            $demandId,
            $demand['record_digest'],
            $actor,
            $disposition,
            trim($rationale),
        ])), 0, 20);
        $resolutionAuthority = null;
        if ($accepted) {
            $authorityId = 'delegate-mission-personnel-resolution-authority-'.substr(hash('sha256', CanonicalJson::encode([
                $id,
                $demand['record_digest'],
                $actor,
            ])), 0, 20);
            $resolutionAuthority = [
                'authority_id' => $authorityId,
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'recipient' => $actor,
                'source_demand' => ['id' => $demandId, 'digest' => $demand['record_digest']],
                'permitted_actions' => ['DETERMINE_PROFESSION', 'DETERMINE_PERSONA_SUITABILITY_AGAINST_GARRISON_FACTS'],
                'consumed' => false,
                'continuing_authority' => false,
            ];
        }

        return $this->save($id, [
            'schema' => 'imperium.guildhall-delegate-mission-capability-demand-intake-disposition/v1',
            'disposition_id' => $id,
            'instance_id' => $demand['instance_id'],
            'source_demand' => ['id' => $demandId, 'digest' => $demand['record_digest']],
            'mission_plan' => $demand['mission_plan'],
            'capability_demand' => $demand['demand'],
            'officer_class' => OfficerClass::Delegate->value,
            'actor' => $actor,
            'disposition' => $disposition,
            'rationale' => trim($rationale),
            'decided_at' => $decidedAt->format(DATE_ATOM),
            'recipient_intake_decided' => true,
            'demand_accepted' => $accepted,
            'demand_refused' => !$accepted,
            'personnel_resolution_authority' => $resolutionAuthority,
            'status' => $accepted
                ? 'DELEGATE_MISSION_CAPABILITY_DEMAND_ACCEPTED_PENDING_PROFESSION_AND_PERSONA_SUITABILITY_RESOLUTION'
                : 'DELEGATE_MISSION_CAPABILITY_DEMAND_REFUSED_NO_PERSONNEL_AUTHORITY',
            'profession_determined' => false,
            'persona_selected' => false,
            'persona_suitability_determined' => false,
            'personnel_use_authority' => false,
            'reservation_authority' => false,
            'retrieval_authority' => false,
            'custody_transfer_authority' => false,
            'profile_derivation_authority' => false,
            'manifestation_assembly_authority' => false,
            'seat_binding_authority' => false,
            'deployment_authority' => false,
            'cognition_authority' => false,
            'provider_invocation_authority' => false,
            'data_access_authority' => false,
            'tool_use_authority' => false,
            'credential_use_authority' => false,
            'perimeter_crossing_authority' => false,
            'external_action_authority' => false,
            'execution_authority' => false,
            'continuing_turn_authority' => false,
            'sealed' => true,
        ]);
    }

    private function validate(string $demandId, array $demand, string $bindingId, array $binding, mixed $guildmaster): void
    {
        if (!$this->valid($demand)
            || 'imperium.delegate-mission-capability-demand/v1' !== ($demand['schema'] ?? null)
            || $demandId !== ($demand['demand_id'] ?? null)
            || OfficerClass::Delegate->value !== ($demand['officer_class'] ?? null)
            || 'DELEGATE_MISSION_CAPABILITY_DEMAND_SEALED_PENDING_GUILDHALL_INTAKE_NO_PERSONNEL_AUTHORITY' !== ($demand['status'] ?? null)
            || 'guildhall.guildmaster' !== ($demand['consumer']['seat'] ?? null)
            || true !== ($demand['consumer']['intake_pending'] ?? null)
            || false !== ($demand['consumer']['delivered'] ?? null)
            || true === ($demand['guildhall_intake_authority'] ?? null)
            || true === ($demand['profession_determination_authority'] ?? null)
            || true === ($demand['persona_selection_authority'] ?? null)
            || true === ($demand['personnel_use_authority'] ?? null)
            || true === ($demand['execution_authority'] ?? null)
            || true !== ($demand['sealed'] ?? null)
            || !is_array($demand['demand'] ?? null)
            || [] === ($demand['demand']['capability_requirements'] ?? [])
            || !is_string($demand['demand']['mission_seat'] ?? null)
            || !$this->valid($binding)
            || 'imperium.guildhall-seat-binding-cohort/v1' !== ($binding['schema'] ?? null)
            || $bindingId !== ($binding['binding_id'] ?? null)
            || ($demand['instance_id'] ?? null) !== ($binding['instance_id'] ?? null)
            || 'guildhall' !== ($binding['office'] ?? null)
            || !in_array($binding['office_status'] ?? null, ['ACTIVE', 'ACTIVE_AWAITING_COMMISSION_ACCEPTANCE'], true)
            || true !== ($binding['binding_atomic'] ?? null)
            || !is_array($guildmaster)
            || 'guildhall.guildmaster' !== ($guildmaster['seat'] ?? null)
            || OfficerClass::Legate->value !== ($guildmaster['officer_class'] ?? null)
            || !is_string($guildmaster['manifestation_id'] ?? null)
            || !is_int($guildmaster['occupancy_generation'] ?? null)
            || $guildmaster['occupancy_generation'] < 1
            || !in_array($guildmaster['status'] ?? null, ['ACTIVE', 'BOUND_PENDING_COMMISSION_ACCEPTANCE'], true)
            || true === ($binding['execution_authority'] ?? null)) {
            throw new \RuntimeException('G495_DELEGATE_MISSION_INTAKE_CHAIN_INVALID');
        }
    }

    private function assertSoleCurrentGuildmaster(string $bindingId, string $instanceId): void
    {
        foreach (glob($this->occupancy.'/guildhall-binding-*.json') ?: [] as $path) {
            $other = $this->read($path, 'G496_GUILDMASTER_OCCUPANCY_CONFLICT');
            $occupant = $other['bindings']['guildhall.guildmaster'] ?? null;
            if (($other['binding_id'] ?? null) !== $bindingId
                && ($other['instance_id'] ?? null) === $instanceId
                && is_array($occupant)
                && in_array($occupant['status'] ?? null, ['ACTIVE', 'BOUND_PENDING_COMMISSION_ACCEPTANCE'], true)) {
                throw new \RuntimeException('G496_GUILDMASTER_OCCUPANCY_CONFLICT');
            }
        }
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException($error);
        }

        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function valid(array $record): bool
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);

        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }

    private function save(string $id, array $record): array
    {
        if (!is_dir($this->dispositions) && !mkdir($this->dispositions, 0770, true) && !is_dir($this->dispositions)) {
            throw new \RuntimeException('G497_DELEGATE_MISSION_INTAKE_PERSISTENCE_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->dispositions.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, 'G497_DELEGATE_MISSION_INTAKE_CONFLICT');
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('G497_DELEGATE_MISSION_INTAKE_CONFLICT');
            }

            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('G497_DELEGATE_MISSION_INTAKE_PERSISTENCE_FAILED');
        }

        return $record;
    }
}
