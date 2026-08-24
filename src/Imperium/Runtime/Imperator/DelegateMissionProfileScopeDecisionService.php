<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Identity\OfficerClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionProfileScopeDecisionService
{
    private const string IMPERATOR_ID = 'imperator-development-root';
    private const array DISPOSITIONS = ['AUTHORIZED', 'REFUSED', 'RETURNED_FOR_REVISION', 'ALTERNATIVE_PROPOSED', 'CLARIFICATION_REQUIRED', 'DEFERRED'];

    private string $requests;
    private string $reservations;
    private string $demands;
    private string $decisions;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->requests = $root.'/var/imperium/curia/delegate-mission-profile-scope-authorization-requests';
        $this->reservations = $root.'/var/imperium/offices/garrison/delegate-mission-persona-reservation-dispositions';
        $this->demands = $root.'/var/imperium/offices/curia/delegate-mission-capability-demands';
        $this->decisions = $root.'/var/imperium/imperator/delegate-mission-profile-scope-decisions';
    }

    public function decide(string $requestId, string $disposition, string $response, ?string $limitations, \DateTimeImmutable $decidedAt): array
    {
        if (!preg_match('/^delegate-mission-profile-scope-authorization-request-[a-f0-9]{20}$/', $requestId)) {
            throw new \InvalidArgumentException('I520_DELEGATE_MISSION_PROFILE_SCOPE_REQUEST_ID_INVALID');
        }
        $disposition = strtoupper(trim($disposition));
        $response = trim($response);
        $limitations = null === $limitations ? null : trim($limitations);
        if (!in_array($disposition, self::DISPOSITIONS, true)
            || '' === $response
            || '' === $limitations
            || ('AUTHORIZED' === $disposition && null === $limitations)) {
            throw new \InvalidArgumentException('I521_DELEGATE_MISSION_PROFILE_SCOPE_DISPOSITION_INVALID');
        }

        $request = $this->read($this->requests.'/'.$requestId.'.json', 'I522_DELEGATE_MISSION_PROFILE_SCOPE_REQUEST_ABSENT');
        $reservation = $this->source($request, 'source_reservation_disposition', $this->reservations, 'imperium.garrison-delegate-mission-persona-reservation-disposition/v1', 'disposition_id');
        $demand = $this->source($request, 'source_capability_demand', $this->demands, 'imperium.delegate-mission-capability-demand/v1', 'demand_id');
        $this->validate($requestId, $request, $reservation, $demand);

        foreach (glob($this->decisions.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'I525_DELEGATE_MISSION_PROFILE_SCOPE_DECISION_CONFLICT');
            if (($prior['source_request']['id'] ?? null) === $requestId) {
                if (($prior['source_request']['digest'] ?? null) === $request['record_digest']
                    && ($prior['disposition'] ?? null) === $disposition
                    && ($prior['response'] ?? null) === $response
                    && ($prior['limitations'] ?? null) === $limitations) {
                    return $prior;
                }
                throw new \RuntimeException('I525_DELEGATE_MISSION_PROFILE_SCOPE_DECISION_CONFLICT');
            }
        }

        $authorized = 'AUTHORIZED' === $disposition;
        $id = 'delegate-mission-profile-scope-decision-'.substr(hash('sha256', CanonicalJson::encode([$requestId, $request['record_digest'], self::IMPERATOR_ID, $disposition, $response, $limitations])), 0, 20);
        $authority = null;
        if ($authorized) {
            $authority = [
                'authority_id' => 'delegate-mission-profile-derivation-authority-'.substr(hash('sha256', CanonicalJson::encode([$id, $request['record_digest'], $request['profile_scope'], $limitations])), 0, 20),
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'holder' => 'conscription.recruiter',
                'purpose' => 'ACCEPT_AND_COMMISSION_DERIVATION_OF_ONE_EXACT_DELEGATE_MISSION_PROFILE',
                'source_request' => ['id' => $requestId, 'digest' => $request['record_digest']],
                'profile_scope_digest' => hash('sha256', CanonicalJson::encode($request['profile_scope'])),
                'limitations' => $limitations,
                'consumed' => false,
                'continuing_authority' => false,
            ];
        }

        return $this->save($id, [
            'schema' => 'imperium.imperator-delegate-mission-profile-scope-decision/v1',
            'decision_id' => $id,
            'instance_id' => $request['instance_id'],
            'officer_class' => OfficerClass::Delegate->value,
            'actor' => ['kind' => 'imperator', 'id' => self::IMPERATOR_ID],
            'authority_basis' => 'development-local-cli',
            'source_request' => ['id' => $requestId, 'digest' => $request['record_digest']],
            'source_reservation_disposition' => $request['source_reservation_disposition'],
            'source_capability_demand' => $request['source_capability_demand'],
            'source_mission_plan' => $request['source_mission_plan'],
            'profile_scope' => $request['profile_scope'],
            'personnel_use_limitations' => $request['imperator_personnel_use_limitations'],
            'disposition' => $disposition,
            'response' => $response,
            'limitations' => $limitations,
            'decided_at' => $decidedAt->format(DATE_ATOM),
            'imperator_decision_recorded' => true,
            'profile_derivation_authorized' => $authorized,
            'profile_derivation_authority' => $authority,
            'profile_derivation_authority_exercisable' => $authorized,
            'conscription_followup_required' => $authorized,
            'curia_followup_required' => in_array($disposition, ['RETURNED_FOR_REVISION', 'ALTERNATIVE_PROPOSED', 'CLARIFICATION_REQUIRED'], true),
            'status' => $authorized
                ? 'DELEGATE_MISSION_PROFILE_DERIVATION_AUTHORIZED_PENDING_CONSCRIPTION_ACCEPTANCE'
                : 'DELEGATE_MISSION_NON_AUTHORIZING_IMPERATOR_PROFILE_SCOPE_DISPOSITION_RECORDED',
            'profile_derived' => false,
            'profile_instantiation_authority' => false,
            'profile_activation_authority' => false,
            'profile_examination_authority' => false,
            'profile_approval_authority' => false,
            'profile_installation_authority' => false,
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

    private function validate(string $id, array $request, array $reservation, array $demand): void
    {
        $scope = $request['profile_scope'] ?? null;
        if (!$this->valid($request)
            || 'imperium.curia-delegate-mission-profile-scope-authorization-request/v1' !== ($request['schema'] ?? null)
            || $id !== ($request['request_id'] ?? null)
            || OfficerClass::Delegate->value !== ($request['officer_class'] ?? null)
            || 'DELEGATE_MISSION_PROFILE_SCOPE_REQUEST_PRESENTED_PENDING_IMPERATOR_DECISION' !== ($request['status'] ?? null)
            || self::IMPERATOR_ID !== ($request['recipient']['id'] ?? null)
            || true !== ($request['recipient']['decision_pending'] ?? null)
            || 'IMMUTABLE_SCOPE_CONSTRUCTION_ONLY' !== ($request['requester']['role'] ?? null)
            || 'DERIVE_ONE_EXACT_DELEGATE_MISSION_PROFILE_ONLY' !== ($request['requested_authority'] ?? null)
            || true !== ($request['profile_scope_construction_authority']['consumed'] ?? null)
            || false !== ($request['profile_scope_construction_authority']['continuing_authority'] ?? null)
            || !is_array($scope)
            || 'MISSION_DELEGATE' !== ($scope['target_kind'] ?? null)
            || OfficerClass::Delegate->value !== ($scope['officer_class'] ?? null)
            || 'curia' !== ($scope['profile_steward'] ?? null)
            || 'laboratorium.alchemist' !== ($scope['prospective_deriver'] ?? null)
            || 'senate' !== ($scope['prospective_examiner'] ?? null)
            || 'imperator' !== ($scope['prospective_approver'] ?? null)
            || 'RESERVED' !== ($reservation['disposition'] ?? null)
            || true !== ($reservation['persona_reserved'] ?? null)
            || ($request['instance_id'] ?? null) !== ($reservation['instance_id'] ?? null)
            || ($request['instance_id'] ?? null) !== ($demand['instance_id'] ?? null)
            || ($scope['profession'] ?? null) !== ($reservation['personnel_commitment']['profession'] ?? null)
            || CanonicalJson::encode($scope['persona'] ?? null) !== CanonicalJson::encode($reservation['personnel_commitment']['persona'] ?? null)
            || ($scope['mission_seat'] ?? null) !== ($demand['demand']['mission_seat'] ?? null)
            || CanonicalJson::encode($scope['capability_requirements'] ?? null) !== CanonicalJson::encode($demand['demand']['capability_requirements'] ?? null)
            || true === ($request['profile_derivation_authority'] ?? null)
            || true === ($request['profile_instantiation_authority'] ?? null)
            || true === ($request['profile_activation_authority'] ?? null)
            || true === ($request['deployment_authority'] ?? null)
            || true === ($request['execution_authority'] ?? null)
            || true !== ($request['sealed'] ?? null)) {
            throw new \RuntimeException('I523_DELEGATE_MISSION_PROFILE_SCOPE_REQUEST_INVALID');
        }
    }

    private function source(array $record, string $field, string $directory, string $schema, string $idField): array
    {
        $source = $record[$field] ?? null;
        if (!is_array($source) || !is_string($source['id'] ?? null) || !is_string($source['digest'] ?? null)) {
            throw new \RuntimeException('I523_DELEGATE_MISSION_PROFILE_SCOPE_REQUEST_INVALID');
        }
        $result = $this->read($directory.'/'.$source['id'].'.json', 'I522_DELEGATE_MISSION_PROFILE_SCOPE_REQUEST_ABSENT');
        if (!$this->valid($result) || ($result['record_digest'] ?? null) !== $source['digest'] || ($result['schema'] ?? null) !== $schema || ($result[$idField] ?? null) !== $source['id']) {
            throw new \RuntimeException('I523_DELEGATE_MISSION_PROFILE_SCOPE_REQUEST_INVALID');
        }

        return $result;
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
        if (!is_dir($this->decisions) && !mkdir($this->decisions, 0770, true) && !is_dir($this->decisions)) {
            throw new \RuntimeException('I524_DELEGATE_MISSION_PROFILE_SCOPE_DECISION_PERSISTENCE_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->decisions.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, 'I525_DELEGATE_MISSION_PROFILE_SCOPE_DECISION_CONFLICT');
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('I525_DELEGATE_MISSION_PROFILE_SCOPE_DECISION_CONFLICT');
            }

            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('I524_DELEGATE_MISSION_PROFILE_SCOPE_DECISION_PERSISTENCE_FAILED');
        }

        return $record;
    }
}
