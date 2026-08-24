<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Garrison;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Identity\OfficerClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionPersonaReservationDispositionService
{
    private string $inbox;
    private string $acceptances;
    private string $decisions;
    private string $custody;
    private string $occupancy;
    private string $dispositions;
    private string $legacyDispositions;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->inbox = $root.'/var/imperium/offices/garrison/delegate-mission-persona-reservation-inbox';
        $this->acceptances = $root.'/var/imperium/offices/guildhall/delegate-mission-personnel-use-acceptances';
        $this->decisions = $root.'/var/imperium/imperator/delegate-mission-personnel-use-decisions';
        $this->custody = $root.'/var/imperium/offices/garrison/custody';
        $this->occupancy = $root.'/var/imperium/offices/garrison/occupancy';
        $this->dispositions = $root.'/var/imperium/offices/garrison/delegate-mission-persona-reservation-dispositions';
        $this->legacyDispositions = $root.'/var/imperium/offices/garrison/persona-reservation-dispositions';
    }

    public function decide(string $requestId, string $bindingId, \DateTimeImmutable $decidedAt): array
    {
        if (!preg_match('/^delegate-mission-persona-reservation-request-[a-f0-9]{20}$/', $requestId)) {
            throw new \InvalidArgumentException('GA510_DELEGATE_MISSION_RESERVATION_REQUEST_ID_INVALID');
        }
        if (!preg_match('/^garrison-constable-binding-[a-f0-9]{20}$/', $bindingId)) {
            throw new \InvalidArgumentException('GA511_CONSTABLE_BINDING_ID_INVALID');
        }

        $request = $this->read($this->inbox.'/'.$requestId.'.json', 'GA512_DELEGATE_MISSION_RESERVATION_REQUEST_ABSENT');
        $acceptanceId = $request['source_acceptance']['id'] ?? '';
        $decisionId = $request['source_decision']['id'] ?? '';
        $acceptance = $this->read($this->acceptances.'/'.$acceptanceId.'.json', 'GA513_DELEGATE_MISSION_RESERVATION_CHAIN_INVALID');
        $decision = $this->read($this->decisions.'/'.$decisionId.'.json', 'GA513_DELEGATE_MISSION_RESERVATION_CHAIN_INVALID');
        $constable = $this->read($this->occupancy.'/'.$bindingId.'.json', 'GA513_DELEGATE_MISSION_RESERVATION_CHAIN_INVALID');
        $this->validate($requestId, $request, $acceptanceId, $acceptance, $decisionId, $decision, $bindingId, $constable);

        foreach (glob($this->dispositions.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'GA518_DELEGATE_MISSION_RESERVATION_DISPOSITION_CONFLICT');
            if (($prior['source_request']['id'] ?? null) === $requestId) {
                if (!$this->valid($prior) || ($prior['source_request']['digest'] ?? null) !== $request['record_digest']) {
                    throw new \RuntimeException('GA518_DELEGATE_MISSION_RESERVATION_DISPOSITION_CONFLICT');
                }

                return $prior;
            }
        }

        if (($request['personnel_commitment'] ?? null) !== ($acceptance['personnel_commitment'] ?? null)
            || ($request['personnel_commitment'] ?? null) !== ($decision['personnel_commitment'] ?? null)) {
            return $this->record($request, $acceptance, $decision, $constable, null, 'DISPOSITION_MISMATCH', 'DELEGATE_MISSION_RESERVATION_REFUSED_DISPOSITION_MISMATCH_NO_AUTHORITY', $decidedAt);
        }

        $persona = $request['personnel_commitment']['persona'];
        $custodyPath = $this->custody.'/'.$persona['custody_id'].'.json';
        if (!is_file($custodyPath)) {
            return $this->record($request, $acceptance, $decision, $constable, null, 'PERSONA_NOT_ADMITTED', 'DELEGATE_MISSION_RESERVATION_REFUSED_PERSONA_NOT_ADMITTED_NO_AUTHORITY', $decidedAt);
        }
        $custody = $this->read($custodyPath, 'GA514_DELEGATE_MISSION_CUSTODY_RECORD_INVALID');
        if (!$this->valid($custody) || 'imperium.garrison-persona-custody/v1' !== ($custody['schema'] ?? null)) {
            throw new \RuntimeException('GA514_DELEGATE_MISSION_CUSTODY_RECORD_INVALID');
        }
        if (($custody['instance_id'] ?? null) !== ($request['instance_id'] ?? null)
            || ($custody['custody_id'] ?? null) !== ($persona['custody_id'] ?? null)
            || ($custody['persona_id'] ?? null) !== ($persona['persona_id'] ?? null)
            || ($custody['persona_version'] ?? null) !== ($persona['persona_version'] ?? null)
            || (null !== ($persona['persona_digest'] ?? null) && ($custody['persona_digest'] ?? null) !== $persona['persona_digest'])
            || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null)) {
            return $this->record($request, $acceptance, $decision, $constable, $custody, 'PERSONA_NOT_ADMITTED', 'DELEGATE_MISSION_RESERVATION_REFUSED_PERSONA_NOT_ADMITTED_NO_AUTHORITY', $decidedAt);
        }
        if (true !== ($custody['available'] ?? null)) {
            return $this->record($request, $acceptance, $decision, $constable, $custody, 'PERSONA_UNAVAILABLE', 'DELEGATE_MISSION_RESERVATION_REFUSED_PERSONA_UNAVAILABLE_NO_AUTHORITY', $decidedAt);
        }
        if ($this->alreadyReserved($persona['custody_id'])) {
            return $this->record($request, $acceptance, $decision, $constable, $custody, 'PERSONA_ALREADY_RESERVED', 'DELEGATE_MISSION_RESERVATION_REFUSED_PERSONA_ALREADY_RESERVED_NO_AUTHORITY', $decidedAt);
        }

        return $this->record($request, $acceptance, $decision, $constable, $custody, 'RESERVED', 'DELEGATE_MISSION_PERSONA_RESERVED_PENDING_PROFILE_SCOPE_CONSTRUCTION', $decidedAt);
    }

    private function record(array $request, array $acceptance, array $decision, array $constable, ?array $custody, string $disposition, string $status, \DateTimeImmutable $decidedAt): array
    {
        $reserved = 'RESERVED' === $disposition;
        $id = 'delegate-mission-persona-reservation-disposition-'.substr(hash('sha256', CanonicalJson::encode([
            $request['request_id'],
            $request['record_digest'],
            $constable['record_digest'],
            $disposition,
            $custody['record_digest'] ?? null,
        ])), 0, 20);
        $scopeAuthority = null;
        if ($reserved) {
            $scopeAuthority = [
                'authority_id' => 'delegate-mission-profile-scope-construction-authority-'.substr(hash('sha256', CanonicalJson::encode([$id, $request['record_digest'], $custody['record_digest']])), 0, 20),
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'holder' => 'curia.seneschal',
                'purpose' => 'CONSTRUCT_ONE_IMMUTABLE_DELEGATE_MISSION_PROFILE_SCOPE_REQUEST',
                'consumed' => false,
                'continuing_authority' => false,
            ];
        }

        return $this->save($id, [
            'schema' => 'imperium.garrison-delegate-mission-persona-reservation-disposition/v1',
            'disposition_id' => $id,
            'instance_id' => $request['instance_id'],
            'officer_class' => OfficerClass::Delegate->value,
            'source_request' => ['id' => $request['request_id'], 'digest' => $request['record_digest']],
            'source_acceptance' => ['id' => $acceptance['acceptance_id'], 'digest' => $acceptance['record_digest']],
            'source_decision' => ['id' => $decision['decision_id'], 'digest' => $decision['record_digest']],
            'personnel_commitment' => $request['personnel_commitment'],
            'imperator_limitations' => $decision['limitations'],
            'constable' => [
                'seat' => 'garrison.constable',
                'officer_class' => OfficerClass::Legate->value,
                'binding_id' => $constable['binding_id'],
                'binding_digest' => $constable['record_digest'],
                'manifestation_id' => $constable['manifestation_id'],
                'occupancy_generation' => $constable['occupancy_generation'],
            ],
            'custody' => null === $custody ? null : [
                'id' => $custody['custody_id'],
                'digest' => $custody['record_digest'],
                'state' => $custody['custody_state'],
                'available_at_decision' => $custody['available'],
                'retained_by' => 'garrison',
            ],
            'disposition' => $disposition,
            'status' => $status,
            'decided_at' => $decidedAt->format(DATE_ATOM),
            'persona_reserved' => $reserved,
            'reservation_effect_committed' => $reserved,
            'profile_scope_construction_authority' => $scopeAuthority,
            'substitution_authority' => false,
            'retrieval_authority' => false,
            'custody_transfer_authority' => false,
            'profile_derivation_authority' => false,
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
            'continuing_turn_authority' => false,
            'sealed' => true,
        ]);
    }

    private function validate(string $requestId, array $request, string $acceptanceId, array $acceptance, string $decisionId, array $decision, string $bindingId, array $constable): void
    {
        if (!$this->valid($request)
            || 'imperium.guildhall-garrison-delegate-mission-persona-reservation-request/v1' !== ($request['schema'] ?? null)
            || $requestId !== ($request['request_id'] ?? null)
            || OfficerClass::Delegate->value !== ($request['officer_class'] ?? null)
            || 'DELEGATE_MISSION_PERSONA_RESERVATION_REQUESTED_PENDING_CONSTABLE_DISPOSITION' !== ($request['status'] ?? null)
            || true !== ($request['reservation_requested'] ?? null)
            || true === ($request['reservation_authority'] ?? null)
            || 'garrison.constable' !== ($request['recipient']['seat'] ?? null)
            || true !== ($request['recipient']['disposition_pending'] ?? null)
            || !$this->valid($acceptance)
            || ($request['source_acceptance']['digest'] ?? null) !== ($acceptance['record_digest'] ?? null)
            || $acceptanceId !== ($acceptance['acceptance_id'] ?? null)
            || true !== ($acceptance['authorization_accepted'] ?? null)
            || true !== ($acceptance['personnel_use_authority']['consumed'] ?? null)
            || 'DELEGATE_MISSION_PERSONNEL_USE_AUTHORIZATION_ACCEPTED_RESERVATION_REQUESTED_PENDING_CONSTABLE_DISPOSITION' !== ($acceptance['status'] ?? null)
            || !$this->valid($decision)
            || ($request['source_decision']['digest'] ?? null) !== ($decision['record_digest'] ?? null)
            || $decisionId !== ($decision['decision_id'] ?? null)
            || 'AUTHORIZED' !== ($decision['disposition'] ?? null)
            || true !== ($decision['personnel_use_authorized'] ?? null)
            || true === ($decision['reservation_authority'] ?? null)
            || !$this->valid($constable)
            || $bindingId !== ($constable['binding_id'] ?? null)
            || ($request['instance_id'] ?? null) !== ($constable['instance_id'] ?? null)
            || 'garrison.constable' !== ($constable['seat'] ?? null)
            || OfficerClass::Legate->value !== ($constable['officer_class'] ?? null)
            || 'ACTIVE' !== ($constable['status'] ?? null)
            || true !== ($constable['persona_reservation_disposition_authority'] ?? null)
            || true === ($constable['selection_authority'] ?? null)
            || true === ($constable['execution_authority'] ?? null)) {
            throw new \RuntimeException('GA513_DELEGATE_MISSION_RESERVATION_CHAIN_INVALID');
        }
    }

    private function alreadyReserved(string $custodyId): bool
    {
        foreach ([$this->dispositions, $this->legacyDispositions] as $directory) {
            foreach (glob($directory.'/*.json') ?: [] as $path) {
                $record = $this->read($path, 'GA515_DELEGATE_MISSION_RESERVATION_LEDGER_INVALID');
                if (!$this->valid($record)) {
                    throw new \RuntimeException('GA515_DELEGATE_MISSION_RESERVATION_LEDGER_INVALID');
                }
                $recordCustody = $record['custody']['id'] ?? $record['custody_id'] ?? null;
                if ($recordCustody === $custodyId && true === ($record['persona_reserved'] ?? null)) {
                    return true;
                }
            }
        }

        return false;
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
            throw new \RuntimeException('GA517_DELEGATE_MISSION_RESERVATION_DISPOSITION_PERSISTENCE_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->dispositions.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, 'GA518_DELEGATE_MISSION_RESERVATION_DISPOSITION_CONFLICT');
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('GA518_DELEGATE_MISSION_RESERVATION_DISPOSITION_CONFLICT');
            }

            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('GA517_DELEGATE_MISSION_RESERVATION_DISPOSITION_PERSISTENCE_FAILED');
        }

        return $record;
    }
}
