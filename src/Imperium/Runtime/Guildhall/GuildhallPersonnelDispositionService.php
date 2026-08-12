<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Guildhall;

use App\Bootstrap\CanonicalJson;

final readonly class GuildhallPersonnelDispositionService
{
    private string $responseDirectory;
    private string $inquiryDirectory;
    private string $determinationDirectory;
    private string $dispositionDirectory;
    private string $foundryInbox;

    public function __construct(string $projectDir)
    {
        $this->responseDirectory = $projectDir.'/var/imperium/offices/guildhall/inventory-responses';
        $this->inquiryDirectory = $projectDir.'/var/imperium/offices/garrison/inbox';
        $this->determinationDirectory = $projectDir.'/var/imperium/offices/guildhall/deliberations';
        $this->dispositionDirectory = $projectDir.'/var/imperium/offices/guildhall/personnel-dispositions';
        $this->foundryInbox = $projectDir.'/var/imperium/offices/foundry/inbox';
    }

    public function resolve(string $responseId): array
    {
        if (!preg_match('/^garrison-response-[a-f0-9]{20}$/', $responseId)) throw new \InvalidArgumentException('G70_RESPONSE_INVALID: exact Garrison response identity is required.');
        $response = $this->read($this->responseDirectory.'/'.$responseId.'.json', 'G71_RESPONSE_ABSENT');
        if (!$this->digestMatches($response) || $responseId !== ($response['response_id'] ?? null)
            || 'AUTHORITATIVE_INVENTORY_FACTS_DELIVERED' !== ($response['status'] ?? null) || true !== ($response['authoritative_inventory_response'] ?? null)
            || 'guildhall.guildmaster' !== ($response['recipient']['seat'] ?? null) || true === ($response['ranking_authority'] ?? null)
            || true === ($response['selection_authority'] ?? null) || true === ($response['execution_authority'] ?? null)) {
            throw new \RuntimeException('G72_RESPONSE_INVALID: exact authoritative non-selecting Garrison response is required.');
        }
        if ([] !== ($response['inventory_records'] ?? null) || 'NO_ADMITTED_PERSONA_CUSTODY_RECORDS_HELD' !== ($response['ledger_finding'] ?? null)) {
            throw new \RuntimeException('G73_INVENTORY_MATCHING_REQUIRED: this leg resolves only an exact empty admitted-Persona ledger.');
        }
        $inquiryId = $response['source_inquiry_id'] ?? null;
        $inquiry = is_string($inquiryId) ? $this->read($this->inquiryDirectory.'/'.$inquiryId.'.json', 'G74_INQUIRY_ABSENT') : [];
        if (!$this->digestMatches($inquiry) || ($response['source_inquiry_digest'] ?? null) !== ($inquiry['record_digest'] ?? null)) throw new \RuntimeException('G75_INQUIRY_CHAIN_INVALID: response does not bind the exact inquiry.');
        $determinationId = $inquiry['source_determination_id'] ?? null;
        $determination = is_string($determinationId) ? $this->read($this->determinationDirectory.'/'.$determinationId.'.json', 'G76_DETERMINATION_ABSENT') : [];
        $synthesis = $determination['guildmaster_synthesis'] ?? null;
        if (!is_array($synthesis) || !$this->digestMatches($determination) || ($inquiry['source_determination_digest'] ?? null) !== ($determination['record_digest'] ?? null)
            || 'PROFESSION_DETERMINED_GARRISON_INVENTORY_REQUIRED' !== ($determination['status'] ?? null) || true === ($determination['final_personnel_disposition'] ?? null)
            || true !== ($determination['sealed'] ?? null) || true === ($determination['execution_authority'] ?? null)
            || ($response['responder']['occupancy_generation'] ?? null) < 1
            || ($response['recipient']['manifestation_id'] ?? null) !== ($determination['occupancy']['guildhall.guildmaster']['manifestation_id'] ?? null)
            || ($response['recipient']['occupancy_generation'] ?? null) !== ($determination['occupancy']['guildhall.guildmaster']['occupancy_generation'] ?? null)) {
            throw new \RuntimeException('G77_DETERMINATION_CHAIN_INVALID: exact pending Profession Determination is unavailable or changed.');
        }
        $professions = $synthesis['required_professions'] ?? null;
        if (!is_array($professions) || [] === $professions || count($professions) !== count(array_unique($professions))) throw new \RuntimeException('G78_PROFESSION_SET_INVALID: exact unique required professions are required.');

        $dispositionId = 'personnel-disposition-'.substr(hash('sha256', CanonicalJson::encode([$responseId, $response['record_digest'], $determinationId, $professions])), 0, 20);
        $disposition = $this->persist($this->dispositionDirectory, $dispositionId, [
            'schema' => 'imperium.guildhall-personnel-disposition/v1', 'disposition_id' => $dispositionId,
            'instance_id' => $response['instance_id'], 'proceeding_id' => $response['proceeding_id'],
            'source_determination_id' => $determinationId, 'source_determination_digest' => $determination['record_digest'],
            'garrison_response_id' => $responseId, 'garrison_response_digest' => $response['record_digest'],
            'guildmaster' => $response['recipient'], 'required_professions' => $professions,
            'available_admitted_personas' => [], 'unresolved_personnel_gaps' => $professions,
            'disposition' => 'PERSONNEL_GAPS_REQUIRE_CONSTRUCTION', 'final_personnel_disposition' => true,
            'foundry_demand_authority' => true, 'construction_authority' => false, 'selection_authority' => false, 'spawning_authority' => false, 'execution_authority' => false,
        ], 'G79_DISPOSITION_REPLAY_CONFLICT');

        $demands = [];
        foreach ($professions as $index => $profession) {
            if (!is_string($profession) || '' === trim($profession)) throw new \RuntimeException('G78_PROFESSION_SET_INVALID: profession must be a non-empty string.');
            $demandId = 'foundry-persona-demand-'.substr(hash('sha256', CanonicalJson::encode([$dispositionId, $disposition['record_digest'], $index, $profession])), 0, 20);
            $demands[] = $this->persist($this->foundryInbox, $demandId, [
                'schema' => 'imperium.foundry-persona-construction-demand/v1', 'demand_id' => $demandId, 'issuer' => 'guildhall.guildmaster',
                'source_disposition_id' => $dispositionId, 'source_disposition_digest' => $disposition['record_digest'],
                'instance_id' => $response['instance_id'], 'proceeding_id' => $response['proceeding_id'], 'profession' => $profession,
                'exemplar_criteria' => $synthesis['exemplar_criteria'] ?? [], 'team_composition' => $synthesis['team_composition'] ?? [], 'boundary_controls' => $synthesis['boundary_controls'] ?? [],
                'status' => 'PENDING_CURIA_CONSTRUCTION_AUTHORIZATION', 'persona_selection_authority' => false, 'construction_authority' => false,
                'spawning_authority' => false, 'seat_binding_authority' => false, 'execution_authority' => false,
            ], 'G80_FOUNDRY_DEMAND_REPLAY_CONFLICT');
        }
        return ['disposition' => $disposition, 'demands' => $demands];
    }

    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function digestMatches(array $record): bool { $digest = $record['record_digest'] ?? null; unset($record['record_digest']); return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record))); }
    private function persist(string $directory, string $id, array $record, string $conflict): array
    {
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) throw new \RuntimeException('Runtime record directory cannot be created.');
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); $path = $directory.'/'.$id.'.json';
        if (is_file($path)) { $existing = $this->read($path, $conflict); if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) throw new \RuntimeException($conflict); return $existing; }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) { @unlink($temporary); throw new \RuntimeException('Runtime record cannot be committed atomically.'); }
        return $record;
    }
}
