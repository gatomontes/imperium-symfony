<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Identity\OfficerClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionPersonnelUseRequestService
{
    private string $resolutions;
    private string $intakes;
    private string $demands;
    private string $garrisonResponses;
    private string $requests;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->resolutions = $root.'/var/imperium/offices/guildhall/delegate-mission-personnel-resolutions';
        $this->intakes = $root.'/var/imperium/offices/guildhall/delegate-mission-capability-demand-intake-dispositions';
        $this->demands = $root.'/var/imperium/offices/curia/delegate-mission-capability-demands';
        $this->garrisonResponses = $root.'/var/imperium/offices/guildhall/inventory-responses';
        $this->requests = $root.'/var/imperium/curia/delegate-mission-personnel-use-requests';
    }

    public function present(string $resolutionId, \DateTimeImmutable $presentedAt): array
    {
        if (!preg_match('/^delegate-mission-personnel-resolution-[a-f0-9]{20}$/', $resolutionId)) {
            throw new \InvalidArgumentException('CUR510_DELEGATE_MISSION_PERSONNEL_RESOLUTION_ID_INVALID');
        }

        $resolution = $this->read($this->resolutions.'/'.$resolutionId.'.json', 'CUR511_DELEGATE_MISSION_PERSONNEL_RESOLUTION_ABSENT');
        $intakeId = $resolution['source_intake']['id'] ?? '';
        $demandId = $resolution['source_demand']['id'] ?? '';
        $responseId = $resolution['source_garrison_facts']['id'] ?? '';
        $intake = $this->read($this->intakes.'/'.$intakeId.'.json', 'CUR512_DELEGATE_MISSION_PERSONNEL_USE_CHAIN_INVALID');
        $demand = $this->read($this->demands.'/'.$demandId.'.json', 'CUR512_DELEGATE_MISSION_PERSONNEL_USE_CHAIN_INVALID');
        $response = $this->read($this->garrisonResponses.'/'.$responseId.'.json', 'CUR512_DELEGATE_MISSION_PERSONNEL_USE_CHAIN_INVALID');
        $this->validate($resolutionId, $resolution, $intakeId, $intake, $demandId, $demand, $responseId, $response);

        foreach (glob($this->requests.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'CUR515_DELEGATE_MISSION_PERSONNEL_USE_REQUEST_CONFLICT');
            if (($prior['source_resolution']['id'] ?? null) === $resolutionId) {
                if (($prior['source_resolution']['digest'] ?? null) !== $resolution['record_digest']) {
                    throw new \RuntimeException('CUR515_DELEGATE_MISSION_PERSONNEL_USE_REQUEST_CONFLICT');
                }

                return $prior;
            }
        }

        $persona = $resolution['persona'];
        $commitment = [
            'officer_class' => OfficerClass::Delegate->value,
            'capability_requirements' => $resolution['capability_correlation']['capability_requirements'],
            'expected_outcomes' => $resolution['capability_correlation']['expected_outcomes'],
            'mission_seat' => $resolution['capability_correlation']['mission_seat'],
            'profession' => $resolution['profession'],
            'persona' => $persona,
            'suitability_disposition' => $resolution['disposition'],
            'suitability_criteria' => $resolution['suitability_criteria'],
            'suitability_evidence_references' => $resolution['evidence_references'],
            'suitability_rationale' => $resolution['rationale'],
            'guildhall_resolution_digest' => $resolution['record_digest'],
            'garrison_facts' => $resolution['source_garrison_facts'],
        ];
        $requestId = 'delegate-mission-personnel-use-request-'.substr(hash('sha256', CanonicalJson::encode([
            $resolutionId,
            $resolution['record_digest'],
            $commitment,
            $demand['mission_plan'],
        ])), 0, 20);

        return $this->save($requestId, [
            'schema' => 'imperium.curia-delegate-mission-personnel-use-request/v1',
            'request_id' => $requestId,
            'instance_id' => $resolution['instance_id'],
            'officer_class' => OfficerClass::Delegate->value,
            'source_resolution' => ['id' => $resolutionId, 'digest' => $resolution['record_digest']],
            'source_intake' => ['id' => $intakeId, 'digest' => $intake['record_digest']],
            'source_demand' => ['id' => $demandId, 'digest' => $demand['record_digest']],
            'source_mission_plan' => $demand['mission_plan'],
            'source_garrison_facts' => ['id' => $responseId, 'digest' => $response['record_digest']],
            'requester' => ['office' => 'curia', 'seat' => 'curia.seneschal', 'role' => 'PRESENTATION_ONLY'],
            'recipient' => ['kind' => 'imperator', 'id' => 'imperator-development-root', 'decision_pending' => true],
            'personnel_commitment' => $commitment,
            'personnel_resolution_boundary' => [
                'resolution_authority' => 'guildhall.guildmaster',
                'garrison_role' => 'CUSTODY_AND_AVAILABILITY_FACTS_ONLY',
                'curia_role' => 'PRESENTATION_ONLY',
                'curia_profession_selection_authority' => false,
                'curia_persona_selection_authority' => false,
                'curia_ranking_authority' => false,
                'curia_substitution_authority' => false,
                'curia_amendment_authority' => false,
            ],
            'question' => 'Authorize mission-bound use of this exact Guildhall-resolved profession and Persona for the exact correlated Delegate capability demand?',
            'requested_authority' => 'ONE_EXACT_DELEGATE_PERSONNEL_USE_COMMITMENT_ONLY',
            'allowed_dispositions' => ['AUTHORIZED', 'REFUSED', 'RETURNED_FOR_REVISION', 'ALTERNATIVE_PROPOSED', 'CLARIFICATION_REQUIRED', 'DEFERRED'],
            'presented_at' => $presentedAt->format(DATE_ATOM),
            'personnel_use_request_authority' => ['id' => $resolution['personnel_use_request_authority']['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'status' => 'DELEGATE_MISSION_PERSONNEL_USE_REQUEST_PRESENTED_PENDING_IMPERATOR_DECISION',
            'imperator_decision_recorded' => false,
            'personnel_use_authority' => false,
            'reservation_authority' => false,
            'retrieval_authority' => false,
            'custody_transfer_authority' => false,
            'profile_derivation_authority' => false,
            'profile_examination_authority' => false,
            'profile_approval_authority' => false,
            'profile_installation_authority' => false,
            'profile_qualification_authority' => false,
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

    private function validate(string $resolutionId, array $resolution, string $intakeId, array $intake, string $demandId, array $demand, string $responseId, array $response): void
    {
        $authority = $resolution['personnel_use_request_authority'] ?? null;
        $persona = $resolution['persona'] ?? null;
        if (!$this->valid($resolution)
            || 'imperium.guildhall-delegate-mission-personnel-resolution/v1' !== ($resolution['schema'] ?? null)
            || $resolutionId !== ($resolution['resolution_id'] ?? null)
            || OfficerClass::Delegate->value !== ($resolution['officer_class'] ?? null)
            || 'SUITABLE' !== ($resolution['disposition'] ?? null)
            || true !== ($resolution['profession_determined'] ?? null)
            || true !== ($resolution['persona_suitability_determined'] ?? null)
            || true !== ($resolution['persona_suitable'] ?? null)
            || 'DELEGATE_MISSION_PROFESSION_AND_PERSONA_SUITABILITY_RESOLVED_PENDING_PERSONNEL_USE_REQUEST' !== ($resolution['status'] ?? null)
            || !is_array($authority)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || 'PRESENT_EXACT_IDENTITY_BEARING_PERSONNEL_USE_REQUEST' !== ($authority['permitted_action'] ?? null)
            || 'curia.seneschal' !== ($authority['recipient'] ?? null)
            || !is_array($persona)
            || !is_string($persona['custody_id'] ?? null)
            || !is_string($persona['persona_id'] ?? null)
            || 'ADMITTED_HELD' !== ($persona['custody_state'] ?? null)
            || true !== ($persona['available'] ?? null)
            || true === ($resolution['personnel_use_authority'] ?? null)
            || true === ($resolution['reservation_authority'] ?? null)
            || true === ($resolution['execution_authority'] ?? null)
            || !$this->valid($intake)
            || ($resolution['source_intake']['digest'] ?? null) !== ($intake['record_digest'] ?? null)
            || $intakeId !== ($intake['disposition_id'] ?? null)
            || 'ACCEPTED' !== ($intake['disposition'] ?? null)
            || !$this->valid($demand)
            || ($resolution['source_demand']['digest'] ?? null) !== ($demand['record_digest'] ?? null)
            || $demandId !== ($demand['demand_id'] ?? null)
            || OfficerClass::Delegate->value !== ($demand['officer_class'] ?? null)
            || !$this->valid($response)
            || ($resolution['source_garrison_facts']['digest'] ?? null) !== ($response['record_digest'] ?? null)
            || $responseId !== ($response['response_id'] ?? null)
            || 'AUTHORITATIVE_INVENTORY_FACTS_DELIVERED' !== ($response['status'] ?? null)
            || true === ($response['selection_authority'] ?? null)
            || true === ($response['reservation_authority'] ?? null)
            || true === ($response['execution_authority'] ?? null)
            || ($resolution['instance_id'] ?? null) !== ($intake['instance_id'] ?? null)
            || ($resolution['instance_id'] ?? null) !== ($demand['instance_id'] ?? null)
            || ($resolution['instance_id'] ?? null) !== ($response['instance_id'] ?? null)
            || ($resolution['capability_correlation']['source_demand_digest'] ?? null) !== ($demand['record_digest'] ?? null)
            || ($resolution['capability_correlation']['capability_requirements'] ?? null) !== ($demand['demand']['capability_requirements'] ?? null)
            || ($resolution['capability_correlation']['mission_seat'] ?? null) !== ($demand['demand']['mission_seat'] ?? null)) {
            throw new \RuntimeException('CUR512_DELEGATE_MISSION_PERSONNEL_USE_CHAIN_INVALID');
        }
        $matches = array_values(array_filter($response['inventory_records'] ?? [], static fn (mixed $record): bool => is_array($record)
            && ($record['custody_id'] ?? null) === $persona['custody_id']
            && ($record['record_digest'] ?? null) === $persona['custody_digest']
            && ($record['persona_id'] ?? null) === $persona['persona_id']
            && ($record['persona_version'] ?? null) === $persona['persona_version']));
        if (1 !== count($matches)) {
            throw new \RuntimeException('CUR513_DELEGATE_MISSION_PERSONA_FACT_MISMATCH');
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
        if (!is_dir($this->requests) && !mkdir($this->requests, 0770, true) && !is_dir($this->requests)) {
            throw new \RuntimeException('CUR514_DELEGATE_MISSION_PERSONNEL_USE_REQUEST_PERSISTENCE_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->requests.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, 'CUR515_DELEGATE_MISSION_PERSONNEL_USE_REQUEST_CONFLICT');
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('CUR515_DELEGATE_MISSION_PERSONNEL_USE_REQUEST_CONFLICT');
            }

            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('CUR514_DELEGATE_MISSION_PERSONNEL_USE_REQUEST_PERSISTENCE_FAILED');
        }

        return $record;
    }
}
