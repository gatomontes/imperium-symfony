<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Guildhall;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Identity\OfficerClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionPersonnelResolutionService
{
    private const array DISPOSITIONS = ['SUITABLE', 'NO_SUITABLE_PERSONA', 'UNRESOLVED'];

    private string $intakes;
    private string $occupancy;
    private string $responses;
    private string $resolutions;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->intakes = $root.'/var/imperium/offices/guildhall/delegate-mission-capability-demand-intake-dispositions';
        $this->occupancy = $root.'/var/imperium/offices/guildhall/occupancy';
        $this->responses = $root.'/var/imperium/offices/guildhall/inventory-responses';
        $this->resolutions = $root.'/var/imperium/offices/guildhall/delegate-mission-personnel-resolutions';
    }

    public function resolve(
        string $intakeDispositionId,
        string $bindingId,
        string $garrisonResponseId,
        string $profession,
        ?string $custodyId,
        string $disposition,
        array $suitabilityCriteria,
        array $evidenceReferences,
        string $rationale,
        \DateTimeImmutable $resolvedAt,
    ): array {
        if (!preg_match('/^delegate-mission-demand-intake-disposition-[a-f0-9]{20}$/', $intakeDispositionId)) {
            throw new \InvalidArgumentException('G500_DELEGATE_MISSION_INTAKE_DISPOSITION_ID_INVALID');
        }
        if (!preg_match('/^guildhall-binding-[a-f0-9]{20}$/', $bindingId)) {
            throw new \InvalidArgumentException('G501_GUILDMASTER_BINDING_ID_INVALID');
        }
        if (!preg_match('/^garrison-response-[a-f0-9]{20}$/', $garrisonResponseId)) {
            throw new \InvalidArgumentException('G502_GARRISON_RESPONSE_ID_INVALID');
        }
        $profession = trim($profession);
        $rationale = trim($rationale);
        if ('' === $profession || '' === $rationale || !in_array($disposition, self::DISPOSITIONS, true)
            || !$this->nonEmptyUniqueStrings($suitabilityCriteria) || !$this->nonEmptyUniqueStrings($evidenceReferences)
            || ('SUITABLE' === $disposition) !== (null !== $custodyId)) {
            throw new \InvalidArgumentException('G503_DELEGATE_MISSION_PERSONNEL_RESOLUTION_INPUT_INVALID');
        }

        $intake = $this->read($this->intakes.'/'.$intakeDispositionId.'.json', 'G504_DELEGATE_MISSION_INTAKE_DISPOSITION_ABSENT');
        $binding = $this->read($this->occupancy.'/'.$bindingId.'.json', 'G505_GUILDMASTER_OCCUPANCY_ABSENT');
        $response = $this->read($this->responses.'/'.$garrisonResponseId.'.json', 'G506_GARRISON_INVENTORY_RESPONSE_ABSENT');
        $guildmaster = $binding['bindings']['guildhall.guildmaster'] ?? null;
        $this->validateChain($intakeDispositionId, $intake, $bindingId, $binding, $guildmaster, $garrisonResponseId, $response);

        foreach (glob($this->resolutions.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'G509_DELEGATE_MISSION_PERSONNEL_RESOLUTION_CONFLICT');
            if (($prior['source_intake']['id'] ?? null) === $intakeDispositionId) {
                return $this->sameResolution($prior, $intake['record_digest'], $bindingId, $garrisonResponseId, $response['record_digest'], $profession, $custodyId, $disposition, $suitabilityCriteria, $evidenceReferences, $rationale);
            }
        }

        $persona = null;
        if (null !== $custodyId) {
            $matches = array_values(array_filter($response['inventory_records'], static fn (mixed $record): bool => is_array($record) && ($record['custody_id'] ?? null) === $custodyId));
            if (1 !== count($matches)) {
                throw new \RuntimeException('G507_DELEGATE_MISSION_PERSONA_NOT_IN_GARRISON_FACTS');
            }
            $custody = $matches[0];
            if ('ADMITTED_HELD' !== ($custody['custody_state'] ?? null) || true !== ($custody['available'] ?? null)) {
                throw new \RuntimeException('G507_DELEGATE_MISSION_PERSONA_NOT_IN_GARRISON_FACTS');
            }
            $persona = [
                'custody_id' => $custody['custody_id'],
                'custody_digest' => $custody['record_digest'],
                'persona_id' => $custody['persona_id'],
                'persona_version' => $custody['persona_version'],
                'persona_digest' => $custody['persona_digest'] ?? null,
                'custody_state' => $custody['custody_state'],
                'available' => $custody['available'],
            ];
        }

        $actor = $intake['actor'];
        $sourceDemand = $intake['source_demand'];
        $authority = $intake['personnel_resolution_authority'];
        $id = 'delegate-mission-personnel-resolution-'.substr(hash('sha256', CanonicalJson::encode([
            $intakeDispositionId,
            $intake['record_digest'],
            $garrisonResponseId,
            $response['record_digest'],
            $profession,
            $persona,
            $disposition,
            $suitabilityCriteria,
            $evidenceReferences,
            $rationale,
        ])), 0, 20);
        $suitable = 'SUITABLE' === $disposition;
        $requestAuthority = null;
        if ($suitable) {
            $requestAuthority = [
                'authority_id' => 'delegate-mission-personnel-use-request-authority-'.substr(hash('sha256', CanonicalJson::encode([$id, $sourceDemand, $profession, $persona])), 0, 20),
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'permitted_action' => 'PRESENT_EXACT_IDENTITY_BEARING_PERSONNEL_USE_REQUEST',
                'recipient' => 'curia.seneschal',
                'consumed' => false,
                'continuing_authority' => false,
            ];
        }

        return $this->save($id, [
            'schema' => 'imperium.guildhall-delegate-mission-personnel-resolution/v1',
            'resolution_id' => $id,
            'instance_id' => $intake['instance_id'],
            'officer_class' => OfficerClass::Delegate->value,
            'source_demand' => $sourceDemand,
            'source_intake' => ['id' => $intakeDispositionId, 'digest' => $intake['record_digest']],
            'source_garrison_facts' => ['id' => $garrisonResponseId, 'digest' => $response['record_digest'], 'ledger_finding' => $response['ledger_finding']],
            'guildmaster' => $actor,
            'capability_correlation' => [
                'capability_requirements' => $intake['capability_demand']['capability_requirements'],
                'expected_outcomes' => $intake['capability_demand']['expected_outcomes'] ?? [],
                'mission_seat' => $intake['capability_demand']['mission_seat'],
                'source_demand_digest' => $sourceDemand['digest'],
            ],
            'profession' => $profession,
            'suitability_criteria' => $suitabilityCriteria,
            'evidence_references' => $evidenceReferences,
            'persona' => $persona,
            'disposition' => $disposition,
            'rationale' => $rationale,
            'resolved_at' => $resolvedAt->format(DATE_ATOM),
            'personnel_resolution_authority' => ['id' => $authority['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'profession_determined' => true,
            'persona_suitability_determined' => 'UNRESOLVED' !== $disposition,
            'persona_suitable' => $suitable,
            'personnel_use_request_authority' => $requestAuthority,
            'status' => match ($disposition) {
                'SUITABLE' => 'DELEGATE_MISSION_PROFESSION_AND_PERSONA_SUITABILITY_RESOLVED_PENDING_PERSONNEL_USE_REQUEST',
                'NO_SUITABLE_PERSONA' => 'DELEGATE_MISSION_PROFESSION_RESOLVED_PERSONNEL_GAP_IDENTIFIED_NO_PERSONNEL_AUTHORITY',
                default => 'DELEGATE_MISSION_PERSONNEL_RESOLUTION_UNRESOLVED_NO_PERSONNEL_AUTHORITY',
            },
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

    private function validateChain(string $intakeId, array $intake, string $bindingId, array $binding, mixed $guildmaster, string $responseId, array $response): void
    {
        $authority = $intake['personnel_resolution_authority'] ?? null;
        if (!$this->valid($intake)
            || 'imperium.guildhall-delegate-mission-capability-demand-intake-disposition/v1' !== ($intake['schema'] ?? null)
            || $intakeId !== ($intake['disposition_id'] ?? null)
            || 'ACCEPTED' !== ($intake['disposition'] ?? null)
            || true !== ($intake['demand_accepted'] ?? null)
            || 'DELEGATE_MISSION_CAPABILITY_DEMAND_ACCEPTED_PENDING_PROFESSION_AND_PERSONA_SUITABILITY_RESOLUTION' !== ($intake['status'] ?? null)
            || !is_array($authority)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || !is_array($intake['capability_demand'] ?? null)
            || [] === ($intake['capability_demand']['capability_requirements'] ?? [])
            || !is_string($intake['capability_demand']['mission_seat'] ?? null)
            || !in_array('DETERMINE_PROFESSION', $authority['permitted_actions'] ?? [], true)
            || !in_array('DETERMINE_PERSONA_SUITABILITY_AGAINST_GARRISON_FACTS', $authority['permitted_actions'] ?? [], true)
            || !$this->valid($binding)
            || $bindingId !== ($binding['binding_id'] ?? null)
            || ($intake['instance_id'] ?? null) !== ($binding['instance_id'] ?? null)
            || !is_array($guildmaster)
            || ($intake['actor']['binding_id'] ?? null) !== $bindingId
            || ($intake['actor']['binding_digest'] ?? null) !== ($binding['record_digest'] ?? null)
            || ($intake['actor']['manifestation_id'] ?? null) !== ($guildmaster['manifestation_id'] ?? null)
            || ($intake['actor']['occupancy_generation'] ?? null) !== ($guildmaster['occupancy_generation'] ?? null)
            || OfficerClass::Legate->value !== ($guildmaster['officer_class'] ?? null)
            || !in_array($guildmaster['status'] ?? null, ['ACTIVE', 'BOUND_PENDING_COMMISSION_ACCEPTANCE'], true)
            || !$this->valid($response)
            || 'imperium.garrison-inventory-response/v1' !== ($response['schema'] ?? null)
            || $responseId !== ($response['response_id'] ?? null)
            || ($intake['instance_id'] ?? null) !== ($response['instance_id'] ?? null)
            || 'AUTHORITATIVE_INVENTORY_FACTS_DELIVERED' !== ($response['status'] ?? null)
            || true !== ($response['authoritative_inventory_response'] ?? null)
            || 'guildhall.guildmaster' !== ($response['recipient']['seat'] ?? null)
            || ($intake['actor']['manifestation_id'] ?? null) !== ($response['recipient']['manifestation_id'] ?? null)
            || ($intake['actor']['occupancy_generation'] ?? null) !== ($response['recipient']['occupancy_generation'] ?? null)
            || true === ($response['ranking_authority'] ?? null)
            || true === ($response['selection_authority'] ?? null)
            || true === ($response['reservation_authority'] ?? null)
            || true === ($response['retrieval_authority'] ?? null)
            || true === ($response['execution_authority'] ?? null)
            || !is_array($response['inventory_records'] ?? null)) {
            throw new \RuntimeException('G508_DELEGATE_MISSION_PERSONNEL_RESOLUTION_CHAIN_INVALID');
        }
        foreach ($response['inventory_records'] as $record) {
            if (!is_array($record) || !$this->valid($record)
                || 'imperium.garrison-persona-custody/v1' !== ($record['schema'] ?? null)
                || ($intake['instance_id'] ?? null) !== ($record['instance_id'] ?? null)
                || 'ADMITTED_HELD' !== ($record['custody_state'] ?? null)) {
                throw new \RuntimeException('G508_DELEGATE_MISSION_PERSONNEL_RESOLUTION_CHAIN_INVALID');
            }
        }
    }

    private function sameResolution(array $prior, string $intakeDigest, string $bindingId, string $responseId, string $responseDigest, string $profession, ?string $custodyId, string $disposition, array $criteria, array $evidence, string $rationale): array
    {
        if (!$this->valid($prior)
            || ($prior['source_intake']['digest'] ?? null) !== $intakeDigest
            || ($prior['guildmaster']['binding_id'] ?? null) !== $bindingId
            || ($prior['source_garrison_facts']['id'] ?? null) !== $responseId
            || ($prior['source_garrison_facts']['digest'] ?? null) !== $responseDigest
            || ($prior['profession'] ?? null) !== $profession
            || ($prior['persona']['custody_id'] ?? null) !== $custodyId
            || ($prior['disposition'] ?? null) !== $disposition
            || ($prior['suitability_criteria'] ?? null) !== $criteria
            || ($prior['evidence_references'] ?? null) !== $evidence
            || ($prior['rationale'] ?? null) !== $rationale) {
            throw new \RuntimeException('G509_DELEGATE_MISSION_PERSONNEL_RESOLUTION_CONFLICT');
        }

        return $prior;
    }

    private function nonEmptyUniqueStrings(mixed $values): bool
    {
        if (!is_array($values) || [] === $values || array_values($values) !== $values) {
            return false;
        }
        foreach ($values as $value) {
            if (!is_string($value) || '' === trim($value)) {
                return false;
            }
        }

        return array_values(array_unique($values)) === $values;
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
        if (!is_dir($this->resolutions) && !mkdir($this->resolutions, 0770, true) && !is_dir($this->resolutions)) {
            throw new \RuntimeException('G509_DELEGATE_MISSION_PERSONNEL_RESOLUTION_PERSISTENCE_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->resolutions.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, 'G509_DELEGATE_MISSION_PERSONNEL_RESOLUTION_CONFLICT');
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('G509_DELEGATE_MISSION_PERSONNEL_RESOLUTION_CONFLICT');
            }

            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('G509_DELEGATE_MISSION_PERSONNEL_RESOLUTION_PERSISTENCE_FAILED');
        }

        return $record;
    }
}
