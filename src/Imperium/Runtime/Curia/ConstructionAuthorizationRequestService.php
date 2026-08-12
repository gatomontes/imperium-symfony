<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;

final readonly class ConstructionAuthorizationRequestService
{
    private string $dispositionDirectory;
    private string $foundryInbox;
    private string $requestDirectory;

    public function __construct(string $projectDir, private ProceedingStore $proceedings)
    {
        $this->dispositionDirectory = $projectDir.'/var/imperium/offices/guildhall/personnel-dispositions';
        $this->foundryInbox = $projectDir.'/var/imperium/offices/foundry/inbox';
        $this->requestDirectory = $projectDir.'/var/imperium/curia/authorization-requests';
    }

    public function request(string $dispositionId): array
    {
        if (!preg_match('/^personnel-disposition-[a-f0-9]{20}$/', $dispositionId)) throw new \InvalidArgumentException('C70_DISPOSITION_INVALID: exact Personnel Disposition identity is required.');
        $disposition = $this->read($this->dispositionDirectory.'/'.$dispositionId.'.json', 'C71_DISPOSITION_ABSENT');
        if (!$this->digestMatches($disposition) || $dispositionId !== ($disposition['disposition_id'] ?? null)
            || 'imperium.guildhall-personnel-disposition/v1' !== ($disposition['schema'] ?? null)
            || 'PERSONNEL_GAPS_REQUIRE_CONSTRUCTION' !== ($disposition['disposition'] ?? null) || true !== ($disposition['final_personnel_disposition'] ?? null)
            || true !== ($disposition['foundry_demand_authority'] ?? null) || true === ($disposition['construction_authority'] ?? null)
            || true === ($disposition['selection_authority'] ?? null) || true === ($disposition['spawning_authority'] ?? null) || true === ($disposition['execution_authority'] ?? null)) {
            throw new \RuntimeException('C72_DISPOSITION_INVALID: exact final non-authorizing Personnel Disposition is required.');
        }
        $proceedingId = $disposition['proceeding_id'] ?? null;
        $proceeding = is_string($proceedingId) ? $this->proceedings->find($proceedingId) : null;
        if (!is_array($proceeding) || ($proceeding['instance_id'] ?? null) !== ($disposition['instance_id'] ?? null)) throw new \RuntimeException('C73_PROCEEDING_INVALID: exact current Curial proceeding is unavailable.');

        $demands = [];
        foreach (glob($this->foundryInbox.'/foundry-persona-demand-*.json') ?: [] as $path) {
            $demand = $this->read($path, 'C74_FOUNDRY_DEMAND_INVALID');
            if ($dispositionId === ($demand['source_disposition_id'] ?? null)) $demands[] = $demand;
        }
        usort($demands, static fn (array $a, array $b): int => ($a['demand_id'] ?? '') <=> ($b['demand_id'] ?? ''));
        $professions = $disposition['unresolved_personnel_gaps'] ?? [];
        if (count($professions) !== count($demands) || [] === $demands) throw new \RuntimeException('C75_FOUNDRY_DEMAND_SET_INVALID: one exact demand per unresolved profession is required.');
        $seen = [];
        foreach ($demands as $demand) {
            $profession = $demand['profession'] ?? null;
            if (!$this->digestMatches($demand) || 'imperium.foundry-persona-construction-demand/v1' !== ($demand['schema'] ?? null)
                || ($disposition['record_digest'] ?? null) !== ($demand['source_disposition_digest'] ?? null)
                || ($disposition['instance_id'] ?? null) !== ($demand['instance_id'] ?? null)
                || 'PENDING_CURIA_CONSTRUCTION_AUTHORIZATION' !== ($demand['status'] ?? null)
                || !is_string($profession) || !in_array($profession, $professions, true) || isset($seen[$profession])
                || true === ($demand['persona_selection_authority'] ?? null) || true === ($demand['construction_authority'] ?? null)
                || true === ($demand['spawning_authority'] ?? null) || true === ($demand['seat_binding_authority'] ?? null) || true === ($demand['execution_authority'] ?? null)) {
                throw new \RuntimeException('C74_FOUNDRY_DEMAND_INVALID: demand set contains an invalid or unauthorized demand.');
            }
            $seen[$profession] = true;
        }
        $demandRefs = array_map(static fn (array $d): array => ['demand_id' => $d['demand_id'], 'record_digest' => $d['record_digest'], 'profession' => $d['profession']], $demands);
        $requestId = 'construction-authorization-request-'.substr(hash('sha256', CanonicalJson::encode([$dispositionId, $disposition['record_digest'], $demandRefs])), 0, 20);
        return $this->persist([
            'schema' => 'imperium.curia-construction-authorization-request/v1', 'request_id' => $requestId,
            'instance_id' => $disposition['instance_id'], 'proceeding_id' => $proceedingId,
            'requester' => ['office' => 'curia', 'seat' => 'curia.seneschal'], 'recipient' => ['kind' => 'imperator', 'id' => 'imperator-development-root'],
            'source_disposition_id' => $dispositionId, 'source_disposition_digest' => $disposition['record_digest'], 'demands' => $demandRefs,
            'question' => 'Authorize Foundry to construct one Persona candidate for each exact unresolved profession demand?',
            'requested_authority' => 'FOUNDRY_PERSONA_CONSTRUCTION_ONLY', 'status' => 'PENDING_IMPERATOR_DECISION',
            'approval_recorded' => false, 'construction_authority' => false, 'persona_selection_authority' => false,
            'spawning_authority' => false, 'seat_binding_authority' => false, 'execution_authority' => false,
        ]);
    }

    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function digestMatches(array $record): bool { $digest = $record['record_digest'] ?? null; unset($record['record_digest']); return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record))); }
    private function persist(array $request): array
    {
        if (!is_dir($this->requestDirectory) && !mkdir($this->requestDirectory, 0770, true) && !is_dir($this->requestDirectory)) throw new \RuntimeException('Curia authorization-request directory cannot be created.');
        $request['record_digest'] = hash('sha256', CanonicalJson::encode($request)); $path = $this->requestDirectory.'/'.$request['request_id'].'.json';
        if (is_file($path)) { $existing = $this->read($path, 'C76_REQUEST_ABSENT'); if (CanonicalJson::encode($existing) !== CanonicalJson::encode($request)) throw new \RuntimeException('C77_REQUEST_REPLAY_CONFLICT'); return $existing; }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($request, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) { @unlink($temporary); throw new \RuntimeException('Construction authorization request cannot be committed atomically.'); }
        return $request;
    }
}
