<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Identity\OfficerClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionProfileScopeAuthorizationRequestService
{
    private string $reservations;
    private string $reservationRequests;
    private string $acceptances;
    private string $decisions;
    private string $personnelRequests;
    private string $resolutions;
    private string $demands;
    private string $requests;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->reservations = $root.'/var/imperium/offices/garrison/delegate-mission-persona-reservation-dispositions';
        $this->reservationRequests = $root.'/var/imperium/offices/garrison/delegate-mission-persona-reservation-inbox';
        $this->acceptances = $root.'/var/imperium/offices/guildhall/delegate-mission-personnel-use-acceptances';
        $this->decisions = $root.'/var/imperium/imperator/delegate-mission-personnel-use-decisions';
        $this->personnelRequests = $root.'/var/imperium/curia/delegate-mission-personnel-use-requests';
        $this->resolutions = $root.'/var/imperium/offices/guildhall/delegate-mission-personnel-resolutions';
        $this->demands = $root.'/var/imperium/offices/curia/delegate-mission-capability-demands';
        $this->requests = $root.'/var/imperium/curia/delegate-mission-profile-scope-authorization-requests';
    }

    public function construct(string $reservationDispositionId, \DateTimeImmutable $constructedAt): array
    {
        if (!preg_match('/^delegate-mission-persona-reservation-disposition-[a-f0-9]{20}$/', $reservationDispositionId)) {
            throw new \InvalidArgumentException('CUR520_DELEGATE_MISSION_RESERVATION_DISPOSITION_ID_INVALID');
        }

        $reservation = $this->read($this->reservations.'/'.$reservationDispositionId.'.json');
        $reservationRequest = $this->source($reservation, 'source_request', $this->reservationRequests, 'imperium.guildhall-garrison-delegate-mission-persona-reservation-request/v1', 'request_id');
        $acceptance = $this->source($reservation, 'source_acceptance', $this->acceptances, 'imperium.guildhall-delegate-mission-personnel-use-acceptance/v1', 'acceptance_id');
        $decision = $this->source($reservation, 'source_decision', $this->decisions, 'imperium.imperator-delegate-mission-personnel-use-decision/v1', 'decision_id');
        $personnelRequest = $this->source($decision, 'source_request', $this->personnelRequests, 'imperium.curia-delegate-mission-personnel-use-request/v1', 'request_id');
        $resolution = $this->source($personnelRequest, 'source_resolution', $this->resolutions, 'imperium.guildhall-delegate-mission-personnel-resolution/v1', 'resolution_id');
        $demand = $this->source($resolution, 'source_demand', $this->demands, 'imperium.delegate-mission-capability-demand/v1', 'demand_id');
        $this->validate($reservationDispositionId, $reservation, $reservationRequest, $acceptance, $decision, $personnelRequest, $resolution, $demand);

        foreach (glob($this->requests.'/*.json') ?: [] as $path) {
            $prior = $this->read($path);
            if (($prior['source_reservation_disposition']['id'] ?? null) === $reservationDispositionId) {
                if (($prior['source_reservation_disposition']['digest'] ?? null) !== $reservation['record_digest']) {
                    throw new \RuntimeException('CUR523_DELEGATE_MISSION_PROFILE_SCOPE_REQUEST_CONFLICT');
                }

                return $prior;
            }
        }

        $commitment = $reservation['personnel_commitment'];
        $mission = $demand['demand'];
        $profileScope = [
            'target_kind' => 'MISSION_DELEGATE',
            'officer_class' => OfficerClass::Delegate->value,
            'profession' => $commitment['profession'],
            'persona' => $commitment['persona'],
            'mission_seat' => $mission['mission_seat'],
            'objective' => $mission['objective'],
            'scope' => $mission['scope'],
            'deliverables' => $mission['deliverables'],
            'constraints' => $mission['constraints'],
            'required_inputs' => $mission['required_inputs'],
            'capability_requirements' => $mission['capability_requirements'],
            'expected_outcomes' => $mission['expected_outcomes'],
            'bounded_duration' => $mission['bounded_duration'],
            'data_requirements' => $mission['data_requirements'],
            'tool_requirements' => $mission['tool_requirements'],
            'credential_requirements' => $mission['credential_requirements'],
            'perimeter_requirements' => $mission['perimeter_requirements'],
            'stop_conditions' => $mission['stop_conditions'],
            'return_conditions' => $mission['return_conditions'],
            'unbinding_conditions' => $mission['unbinding_conditions'],
            'custody_restoration_conditions' => $mission['custody_restoration_conditions'],
            'retirement_conditions' => $mission['retirement_conditions'],
            'profile_steward' => 'curia',
            'prospective_deriver' => 'laboratorium.alchemist',
            'prospective_examiner' => 'senate',
            'prospective_approver' => 'imperator',
        ];
        $id = 'delegate-mission-profile-scope-authorization-request-'.substr(hash('sha256', CanonicalJson::encode([$reservationDispositionId, $reservation['record_digest'], $profileScope])), 0, 20);

        return $this->save($id, [
            'schema' => 'imperium.curia-delegate-mission-profile-scope-authorization-request/v1',
            'request_id' => $id,
            'instance_id' => $reservation['instance_id'],
            'officer_class' => OfficerClass::Delegate->value,
            'source_reservation_disposition' => ['id' => $reservationDispositionId, 'digest' => $reservation['record_digest']],
            'source_reservation_request' => ['id' => $reservationRequest['request_id'], 'digest' => $reservationRequest['record_digest']],
            'source_personnel_use_acceptance' => ['id' => $acceptance['acceptance_id'], 'digest' => $acceptance['record_digest']],
            'source_personnel_use_decision' => ['id' => $decision['decision_id'], 'digest' => $decision['record_digest']],
            'source_personnel_use_request' => ['id' => $personnelRequest['request_id'], 'digest' => $personnelRequest['record_digest']],
            'source_guildhall_resolution' => ['id' => $resolution['resolution_id'], 'digest' => $resolution['record_digest']],
            'source_capability_demand' => ['id' => $demand['demand_id'], 'digest' => $demand['record_digest']],
            'source_mission_plan' => $demand['mission_plan'],
            'requester' => ['office' => 'curia', 'seat' => 'curia.seneschal', 'role' => 'IMMUTABLE_SCOPE_CONSTRUCTION_ONLY'],
            'recipient' => ['kind' => 'imperator', 'id' => 'imperator-development-root', 'decision_pending' => true],
            'profile_scope' => $profileScope,
            'imperator_personnel_use_limitations' => $decision['limitations'],
            'question' => 'Authorize derivation of one mission-bound Delegate Profile within this exact immutable scope?',
            'requested_authority' => 'DERIVE_ONE_EXACT_DELEGATE_MISSION_PROFILE_ONLY',
            'profile_scope_construction_authority' => ['id' => $reservation['profile_scope_construction_authority']['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'constructed_at' => $constructedAt->format(DATE_ATOM),
            'status' => 'DELEGATE_MISSION_PROFILE_SCOPE_REQUEST_PRESENTED_PENDING_IMPERATOR_DECISION',
            'profile_derivation_authority' => false,
            'profile_instantiation_authority' => false,
            'profile_activation_authority' => false,
            'profile_examination_authority' => false,
            'profile_approval_authority' => false,
            'manifestation_assembly_authority' => false,
            'seat_binding_authority' => false,
            'deployment_authority' => false,
            'operational_use_authority' => false,
            'cognition_authority' => false,
            'provider_invocation_authority' => false,
            'data_access_authority' => false,
            'tool_use_authority' => false,
            'credential_use_authority' => false,
            'perimeter_crossing_authority' => false,
            'external_action_authority' => false,
            'execution_authority' => false,
            'mission_plan_amendment_authority' => false,
            'follow_up_commission_authority' => false,
            'continuing_turn_authority' => false,
            'sealed' => true,
        ]);
    }

    private function validate(string $id, array $reservation, array $reservationRequest, array $acceptance, array $decision, array $personnelRequest, array $resolution, array $demand): void
    {
        $authority = $reservation['profile_scope_construction_authority'] ?? null;
        $commitment = $reservation['personnel_commitment'] ?? null;
        $mission = $demand['demand'] ?? null;
        $required = ['objective', 'scope', 'deliverables', 'constraints', 'required_inputs', 'capability_requirements', 'expected_outcomes', 'mission_seat', 'bounded_duration', 'data_requirements', 'tool_requirements', 'credential_requirements', 'perimeter_requirements', 'stop_conditions', 'return_conditions', 'unbinding_conditions', 'custody_restoration_conditions', 'retirement_conditions'];
        if (!$this->valid($reservation)
            || 'imperium.garrison-delegate-mission-persona-reservation-disposition/v1' !== ($reservation['schema'] ?? null)
            || $id !== ($reservation['disposition_id'] ?? null)
            || OfficerClass::Delegate->value !== ($reservation['officer_class'] ?? null)
            || 'RESERVED' !== ($reservation['disposition'] ?? null)
            || 'DELEGATE_MISSION_PERSONA_RESERVED_PENDING_PROFILE_SCOPE_CONSTRUCTION' !== ($reservation['status'] ?? null)
            || true !== ($reservation['persona_reserved'] ?? null)
            || true !== ($reservation['reservation_effect_committed'] ?? null)
            || !is_array($authority)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || 'curia.seneschal' !== ($authority['holder'] ?? null)
            || 'CONSTRUCT_ONE_IMMUTABLE_DELEGATE_MISSION_PROFILE_SCOPE_REQUEST' !== ($authority['purpose'] ?? null)
            || !is_array($commitment) || !is_array($commitment['persona'] ?? null)
            || !is_array($mission)
            || 'AUTHORIZED' !== ($decision['disposition'] ?? null)
            || count(array_unique(array_map(static fn (array $record): mixed => $record['instance_id'] ?? null, [$reservation, $reservationRequest, $acceptance, $decision, $personnelRequest, $resolution, $demand]), SORT_REGULAR)) !== 1
            || CanonicalJson::encode($commitment) !== CanonicalJson::encode($reservationRequest['personnel_commitment'] ?? null)
            || CanonicalJson::encode($commitment) !== CanonicalJson::encode($acceptance['personnel_commitment'] ?? null)
            || CanonicalJson::encode($commitment) !== CanonicalJson::encode($decision['personnel_commitment'] ?? null)
            || CanonicalJson::encode($commitment) !== CanonicalJson::encode($personnelRequest['personnel_commitment'] ?? null)
            || ($commitment['profession'] ?? null) !== ($resolution['profession'] ?? null)
            || CanonicalJson::encode($commitment['persona']) !== CanonicalJson::encode($resolution['persona'] ?? null)
            || CanonicalJson::encode($commitment['capability_requirements'] ?? null) !== CanonicalJson::encode($mission['capability_requirements'] ?? null)
            || ($commitment['mission_seat'] ?? null) !== ($mission['mission_seat'] ?? null)) {
            throw new \RuntimeException('CUR522_DELEGATE_MISSION_PROFILE_SCOPE_CHAIN_INVALID');
        }
        foreach ($required as $field) {
            if (!array_key_exists($field, $mission)) {
                throw new \RuntimeException('CUR522_DELEGATE_MISSION_PROFILE_SCOPE_CHAIN_INVALID');
            }
        }
    }

    private function source(array $record, string $field, string $directory, string $schema, string $idField): array
    {
        $source = $record[$field] ?? null;
        if (!is_array($source) || !is_string($source['id'] ?? null) || !is_string($source['digest'] ?? null)) {
            throw new \RuntimeException('CUR522_DELEGATE_MISSION_PROFILE_SCOPE_CHAIN_INVALID');
        }
        $result = $this->read($directory.'/'.$source['id'].'.json');
        if (!$this->valid($result)
            || ($result['record_digest'] ?? null) !== $source['digest']
            || ($result['schema'] ?? null) !== $schema
            || ($result[$idField] ?? null) !== $source['id']) {
            throw new \RuntimeException('CUR522_DELEGATE_MISSION_PROFILE_SCOPE_CHAIN_INVALID');
        }

        return $result;
    }

    private function read(string $path): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException('CUR521_DELEGATE_MISSION_PROFILE_SCOPE_SOURCE_ABSENT');
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
        if (!is_dir($this->requests) && !mkdir($this->requests, 0770, true) && !is_dir($this->requests)) {
            throw new \RuntimeException('CUR524_DELEGATE_MISSION_PROFILE_SCOPE_REQUEST_PERSISTENCE_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->requests.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path);
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('CUR523_DELEGATE_MISSION_PROFILE_SCOPE_REQUEST_CONFLICT');
            }

            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('CUR524_DELEGATE_MISSION_PROFILE_SCOPE_REQUEST_PERSISTENCE_FAILED');
        }

        return $record;
    }
}
