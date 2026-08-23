<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;

final readonly class PersonaProductionCaseService
{
    private string $acceptanceDirectory;
    private string $demandDirectory;
    private string $caseDirectory;

    public function __construct(string $projectDir)
    {
        $this->acceptanceDirectory = $projectDir.'/var/imperium/offices/foundry/acceptances';
        $this->demandDirectory = $projectDir.'/var/imperium/offices/foundry/inbox';
        $this->caseDirectory = $projectDir.'/var/imperium/offices/foundry/production-cases';
    }

    public function open(string $acceptanceId): array
    {
        if (!preg_match('/^foundry-acceptance-[a-f0-9]{20}$/', $acceptanceId)) throw new \InvalidArgumentException('F70_ACCEPTANCE_INVALID: exact Foundry acceptance identity is required.');
        $acceptance = $this->read($this->acceptanceDirectory.'/'.$acceptanceId.'.json', 'F71_ACCEPTANCE_ABSENT');
        $demands = $acceptance['authorized_demands'] ?? null;
        if (!$this->digestMatches($acceptance) || $acceptanceId !== ($acceptance['acceptance_id'] ?? null)
            || 'imperium.foundry-authorization-acceptance/v1' !== ($acceptance['schema'] ?? null)
            || 'foundry.artificer' !== ($acceptance['actor']['seat'] ?? null) || 'ACCEPTED_FOR_EXACT_CONSTRUCTION' !== ($acceptance['disposition'] ?? null)
            || true !== ($acceptance['recipient_acceptance'] ?? null) || true !== ($acceptance['foundry_construction_authority'] ?? null)
            || true === ($acceptance['persona_selection_authority'] ?? null) || true === ($acceptance['spawning_authority'] ?? null)
            || true === ($acceptance['seat_binding_authority'] ?? null) || true === ($acceptance['execution_authority'] ?? null)
            || !is_array($demands) || [] === $demands) {
            throw new \RuntimeException('F72_ACCEPTANCE_INVALID: exact bounded accepted Foundry authorization is required.');
        }
        $opened = []; $seen = [];
        foreach ($demands as $index => $reference) {
            $demandId = is_array($reference) ? ($reference['demand_id'] ?? null) : null;
            if (!is_string($demandId) || isset($seen[$demandId])) throw new \RuntimeException('F73_DEMAND_SET_INVALID: authorized demand references must be exact and unique.');
            $demand = $this->read($this->demandDirectory.'/'.$demandId.'.json', 'F74_DEMAND_ABSENT');
            if (!$this->digestMatches($demand) || $demandId !== ($demand['demand_id'] ?? null)
                || 'imperium.foundry-persona-construction-demand/v1' !== ($demand['schema'] ?? null)
                || ($acceptance['instance_id'] ?? null) !== ($demand['instance_id'] ?? null)
                || ($reference['record_digest'] ?? null) !== ($demand['record_digest'] ?? null)
                || ($reference['profession'] ?? null) !== ($demand['profession'] ?? null)
                || 'PENDING_CURIA_CONSTRUCTION_AUTHORIZATION' !== ($demand['status'] ?? null)
                || true === ($demand['construction_authority'] ?? null) || true === ($demand['persona_selection_authority'] ?? null)
                || true === ($demand['spawning_authority'] ?? null) || true === ($demand['seat_binding_authority'] ?? null)
                || true === ($demand['execution_authority'] ?? null)) {
                throw new \RuntimeException('F75_DEMAND_INVALID: exact unchanged authorized Persona demand is required.');
            }
            $caseId = 'persona-production-'.substr(hash('sha256', CanonicalJson::encode([$acceptanceId, $acceptance['record_digest'], $index, $demandId, $demand['record_digest']])), 0, 20);
            $opened[] = $this->persist($caseId, [
                'schema' => 'imperium.foundry-persona-production-case/v1', 'case_id' => $caseId,
                'instance_id' => $demand['instance_id'], 'proceeding_id' => $demand['proceeding_id'], 'queue_position' => $index + 1,
                'profession' => $demand['profession'], 'source_demand_id' => $demandId, 'source_demand_digest' => $demand['record_digest'],
                'source_disposition_id' => $demand['source_disposition_id'], 'source_disposition_digest' => $demand['source_disposition_digest'],
                'authorization_acceptance_id' => $acceptanceId, 'authorization_acceptance_digest' => $acceptance['record_digest'],
                'artificer' => $acceptance['actor'], 'exemplar_criteria' => $demand['exemplar_criteria'] ?? [],
                'team_composition' => $demand['team_composition'] ?? [], 'boundary_controls' => $demand['boundary_controls'] ?? [],
                'status' => 'OPEN_PENDING_SPECIALIZED_INPUTS', 'construction_authority' => true,
                'persona_selection_authority' => false, 'spawning_authority' => false, 'admission_authority' => false,
                'seat_binding_authority' => false, 'execution_authority' => false,
            ]);
            $seen[$demandId] = true;
        }
        return ['acceptance_id' => $acceptanceId, 'artificer' => $acceptance['actor'], 'cases' => $opened];
    }

    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function digestMatches(array $record): bool { $digest = $record['record_digest'] ?? null; unset($record['record_digest']); return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record))); }
    private function persist(string $caseId, array $case): array
    {
        if (!is_dir($this->caseDirectory) && !mkdir($this->caseDirectory, 0770, true) && !is_dir($this->caseDirectory)) throw new \RuntimeException('Foundry production-case directory cannot be created.');
        $case['record_digest'] = hash('sha256', CanonicalJson::encode($case)); $path = $this->caseDirectory.'/'.$caseId.'.json';
        if (is_file($path)) { $existing = $this->read($path, 'F76_CASE_REPLAY_CONFLICT'); if (CanonicalJson::encode($existing) !== CanonicalJson::encode($case)) throw new \RuntimeException('F76_CASE_REPLAY_CONFLICT: production-case identity is already bound differently.'); return $existing; }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($case, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) { @unlink($temporary); throw new \RuntimeException('Foundry production case cannot be committed atomically.'); }
        return $case;
    }
}
