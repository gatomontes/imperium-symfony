<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Guildhall;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Identity\OfficerClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionPersonnelUseAcceptanceService
{
    private string $decisions;
    private string $requests;
    private string $resolutions;
    private string $occupancy;
    private string $acceptances;
    private string $garrisonInbox;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->decisions = $root.'/var/imperium/imperator/delegate-mission-personnel-use-decisions';
        $this->requests = $root.'/var/imperium/curia/delegate-mission-personnel-use-requests';
        $this->resolutions = $root.'/var/imperium/offices/guildhall/delegate-mission-personnel-resolutions';
        $this->occupancy = $root.'/var/imperium/offices/guildhall/occupancy';
        $this->acceptances = $root.'/var/imperium/offices/guildhall/delegate-mission-personnel-use-acceptances';
        $this->garrisonInbox = $root.'/var/imperium/offices/garrison/delegate-mission-persona-reservation-inbox';
    }

    public function accept(string $decisionId, string $bindingId, \DateTimeImmutable $acceptedAt): array
    {
        if (!preg_match('/^delegate-mission-personnel-use-decision-[a-f0-9]{20}$/', $decisionId)) {
            throw new \InvalidArgumentException('G510_DELEGATE_MISSION_PERSONNEL_USE_DECISION_ID_INVALID');
        }
        if (!preg_match('/^guildhall-binding-[a-f0-9]{20}$/', $bindingId)) {
            throw new \InvalidArgumentException('G511_GUILDMASTER_BINDING_ID_INVALID');
        }

        $decision = $this->read($this->decisions.'/'.$decisionId.'.json', 'G512_DELEGATE_MISSION_PERSONNEL_USE_DECISION_ABSENT');
        $requestId = $decision['source_request']['id'] ?? '';
        $resolutionId = $decision['source_resolution']['id'] ?? '';
        $request = $this->read($this->requests.'/'.$requestId.'.json', 'G513_DELEGATE_MISSION_PERSONNEL_USE_ACCEPTANCE_CHAIN_INVALID');
        $resolution = $this->read($this->resolutions.'/'.$resolutionId.'.json', 'G513_DELEGATE_MISSION_PERSONNEL_USE_ACCEPTANCE_CHAIN_INVALID');
        $binding = $this->read($this->occupancy.'/'.$bindingId.'.json', 'G513_DELEGATE_MISSION_PERSONNEL_USE_ACCEPTANCE_CHAIN_INVALID');
        $guildmaster = $binding['bindings']['guildhall.guildmaster'] ?? null;
        $this->validate($decisionId, $decision, $requestId, $request, $resolutionId, $resolution, $bindingId, $binding, $guildmaster);

        $acceptanceId = 'delegate-mission-personnel-use-acceptance-'.substr(hash('sha256', CanonicalJson::encode([
            $decisionId,
            $decision['record_digest'],
            $bindingId,
            $binding['record_digest'],
            $decision['personnel_commitment'],
        ])), 0, 20);
        $reservationRequestId = 'delegate-mission-persona-reservation-request-'.substr(hash('sha256', CanonicalJson::encode([
            $acceptanceId,
            $decision['record_digest'],
            $decision['personnel_commitment'],
        ])), 0, 20);
        $acceptancePath = $this->acceptances.'/'.$acceptanceId.'.json';
        $reservationPath = $this->garrisonInbox.'/'.$reservationRequestId.'.json';
        if (is_file($acceptancePath) || is_file($reservationPath)) {
            if (!is_file($acceptancePath) || !is_file($reservationPath)) {
                throw new \RuntimeException('G516_DELEGATE_MISSION_PERSONNEL_USE_ACCEPTANCE_CONFLICT');
            }
            $priorAcceptance = $this->read($acceptancePath, 'G516_DELEGATE_MISSION_PERSONNEL_USE_ACCEPTANCE_CONFLICT');
            $priorReservation = $this->read($reservationPath, 'G517_DELEGATE_MISSION_RESERVATION_REQUEST_CONFLICT');
            if (!$this->valid($priorAcceptance) || !$this->valid($priorReservation)
                || ($priorAcceptance['source_decision']['digest'] ?? null) !== $decision['record_digest']
                || ($priorAcceptance['guildmaster']['binding_id'] ?? null) !== $bindingId
                || ($priorReservation['source_acceptance']['digest'] ?? null) !== ($priorAcceptance['record_digest'] ?? null)) {
                throw new \RuntimeException('G516_DELEGATE_MISSION_PERSONNEL_USE_ACCEPTANCE_CONFLICT');
            }

            return ['acceptance' => $priorAcceptance, 'reservation_request' => $priorReservation];
        }
        $actor = [
            'office' => 'guildhall',
            'seat' => 'guildhall.guildmaster',
            'officer_class' => OfficerClass::Legate->value,
            'binding_id' => $bindingId,
            'binding_digest' => $binding['record_digest'],
            'manifestation_id' => $guildmaster['manifestation_id'],
            'occupancy_generation' => $guildmaster['occupancy_generation'],
        ];
        $acceptance = $this->persist($this->acceptances, $acceptanceId, [
            'schema' => 'imperium.guildhall-delegate-mission-personnel-use-acceptance/v1',
            'acceptance_id' => $acceptanceId,
            'instance_id' => $decision['instance_id'],
            'officer_class' => OfficerClass::Delegate->value,
            'source_decision' => ['id' => $decisionId, 'digest' => $decision['record_digest']],
            'source_request' => ['id' => $requestId, 'digest' => $request['record_digest']],
            'source_resolution' => ['id' => $resolutionId, 'digest' => $resolution['record_digest']],
            'guildmaster' => $actor,
            'personnel_commitment' => $decision['personnel_commitment'],
            'imperator_limitations' => $decision['limitations'],
            'authorization_accepted' => true,
            'personnel_use_authority' => ['id' => $decision['personnel_use_authority']['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'reservation_request' => ['id' => $reservationRequestId, 'recipient' => 'garrison.constable', 'issued' => true],
            'accepted_at' => $acceptedAt->format(DATE_ATOM),
            'status' => 'DELEGATE_MISSION_PERSONNEL_USE_AUTHORIZATION_ACCEPTED_RESERVATION_REQUESTED_PENDING_CONSTABLE_DISPOSITION',
            'persona_reserved' => false,
            'reservation_authority' => false,
            'retrieval_authority' => false,
            'custody_transfer_authority' => false,
            'profile_derivation_authority' => false,
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
            'continuing_turn_authority' => false,
            'sealed' => true,
        ], 'G516_DELEGATE_MISSION_PERSONNEL_USE_ACCEPTANCE_CONFLICT');

        $reservationRequest = $this->persist($this->garrisonInbox, $reservationRequestId, [
            'schema' => 'imperium.guildhall-garrison-delegate-mission-persona-reservation-request/v1',
            'request_id' => $reservationRequestId,
            'instance_id' => $decision['instance_id'],
            'officer_class' => OfficerClass::Delegate->value,
            'issuer' => $actor,
            'recipient' => ['office' => 'garrison', 'seat' => 'garrison.constable', 'disposition_pending' => true],
            'source_acceptance' => ['id' => $acceptanceId, 'digest' => $acceptance['record_digest']],
            'source_decision' => ['id' => $decisionId, 'digest' => $decision['record_digest']],
            'source_request' => ['id' => $requestId, 'digest' => $request['record_digest']],
            'source_resolution' => ['id' => $resolutionId, 'digest' => $resolution['record_digest']],
            'source_demand' => $decision['source_demand'],
            'source_mission_plan' => $decision['source_mission_plan'],
            'personnel_commitment' => $decision['personnel_commitment'],
            'imperator_limitations' => $decision['limitations'],
            'reservation_requested' => true,
            'requested_effect' => 'RESERVE_ONE_EXACT_ADMITTED_PERSONA_FOR_DELEGATE_PROFILE_PREPARATION',
            'status' => 'DELEGATE_MISSION_PERSONA_RESERVATION_REQUESTED_PENDING_CONSTABLE_DISPOSITION',
            'persona_reserved' => false,
            'reservation_authority' => false,
            'retrieval_authority' => false,
            'custody_transfer_authority' => false,
            'profile_derivation_authority' => false,
            'manifestation_assembly_authority' => false,
            'seat_binding_authority' => false,
            'deployment_authority' => false,
            'operational_use_authority' => false,
            'cognition_authority' => false,
            'provider_invocation_authority' => false,
            'tool_use_authority' => false,
            'credential_use_authority' => false,
            'perimeter_crossing_authority' => false,
            'external_action_authority' => false,
            'execution_authority' => false,
            'continuing_turn_authority' => false,
            'sealed' => true,
        ], 'G517_DELEGATE_MISSION_RESERVATION_REQUEST_CONFLICT');

        return ['acceptance' => $acceptance, 'reservation_request' => $reservationRequest];
    }

    private function validate(string $decisionId, array $decision, string $requestId, array $request, string $resolutionId, array $resolution, string $bindingId, array $binding, mixed $guildmaster): void
    {
        $authority = $decision['personnel_use_authority'] ?? null;
        if (!$this->valid($decision)
            || 'imperium.imperator-delegate-mission-personnel-use-decision/v1' !== ($decision['schema'] ?? null)
            || $decisionId !== ($decision['decision_id'] ?? null)
            || 'AUTHORIZED' !== ($decision['disposition'] ?? null)
            || true !== ($decision['personnel_use_authorized'] ?? null)
            || true !== ($decision['personnel_use_authority_exercisable'] ?? null)
            || 'DELEGATE_MISSION_PERSONNEL_USE_AUTHORIZED_PENDING_GUILDHALL_ACCEPTANCE' !== ($decision['status'] ?? null)
            || !is_array($authority)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || 'guildhall.guildmaster' !== ($authority['holder'] ?? null)
            || 'ACCEPT_ONE_EXACT_AUTHORIZED_DELEGATE_PERSONNEL_COMMITMENT' !== ($authority['purpose'] ?? null)
            || ($authority['personnel_commitment_digest'] ?? null) !== hash('sha256', CanonicalJson::encode($decision['personnel_commitment'] ?? null))
            || !is_string($decision['limitations'] ?? null)
            || '' === trim($decision['limitations'])
            || true === ($decision['reservation_authority'] ?? null)
            || true === ($decision['execution_authority'] ?? null)
            || !$this->valid($request)
            || ($decision['source_request']['digest'] ?? null) !== ($request['record_digest'] ?? null)
            || $requestId !== ($request['request_id'] ?? null)
            || ($decision['personnel_commitment'] ?? null) !== ($request['personnel_commitment'] ?? null)
            || !$this->valid($resolution)
            || ($decision['source_resolution']['digest'] ?? null) !== ($resolution['record_digest'] ?? null)
            || $resolutionId !== ($resolution['resolution_id'] ?? null)
            || ($decision['personnel_commitment']['guildhall_resolution_digest'] ?? null) !== ($resolution['record_digest'] ?? null)
            || !$this->valid($binding)
            || $bindingId !== ($binding['binding_id'] ?? null)
            || ($decision['instance_id'] ?? null) !== ($binding['instance_id'] ?? null)
            || !is_array($guildmaster)
            || OfficerClass::Legate->value !== ($guildmaster['officer_class'] ?? null)
            || ($resolution['guildmaster']['binding_id'] ?? null) !== $bindingId
            || ($resolution['guildmaster']['binding_digest'] ?? null) !== ($binding['record_digest'] ?? null)
            || ($resolution['guildmaster']['manifestation_id'] ?? null) !== ($guildmaster['manifestation_id'] ?? null)
            || ($resolution['guildmaster']['occupancy_generation'] ?? null) !== ($guildmaster['occupancy_generation'] ?? null)
            || !in_array($guildmaster['status'] ?? null, ['ACTIVE', 'BOUND_PENDING_COMMISSION_ACCEPTANCE'], true)) {
            throw new \RuntimeException('G513_DELEGATE_MISSION_PERSONNEL_USE_ACCEPTANCE_CHAIN_INVALID');
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

    private function persist(string $directory, string $id, array $record, string $conflict): array
    {
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new \RuntimeException('G515_DELEGATE_MISSION_PERSONNEL_USE_ACCEPTANCE_PERSISTENCE_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $directory.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, $conflict);
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException($conflict);
            }

            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('G515_DELEGATE_MISSION_PERSONNEL_USE_ACCEPTANCE_PERSISTENCE_FAILED');
        }

        return $record;
    }
}
