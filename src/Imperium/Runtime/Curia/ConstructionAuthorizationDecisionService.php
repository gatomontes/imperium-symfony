<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;

final readonly class ConstructionAuthorizationDecisionService
{
    private const string IMPERATOR_ID = 'imperator-development-root';

    private string $requestDirectory;
    private string $demandDirectory;
    private string $decisionDirectory;

    public function __construct(string $projectDir)
    {
        $this->requestDirectory = $projectDir.'/var/imperium/curia/authorization-requests';
        $this->demandDirectory = $projectDir.'/var/imperium/offices/foundry/inbox';
        $this->decisionDirectory = $projectDir.'/var/imperium/curia/authorization-decisions';
    }

    public function authorize(string $requestId, ?string $actId = null): array
    {
        if (!preg_match('/^construction-authorization-request-[a-f0-9]{20}$/', $requestId)) {
            throw new \InvalidArgumentException('C80_CONSTRUCTION_REQUEST_INVALID: exact request identity is required.');
        }
        $request = $this->read($this->requestDirectory.'/'.$requestId.'.json', 'C81_CONSTRUCTION_REQUEST_ABSENT');
        if (!$this->digestMatches($request) || $requestId !== ($request['request_id'] ?? null)
            || 'imperium.curia-construction-authorization-request/v1' !== ($request['schema'] ?? null)
            || 'PENDING_IMPERATOR_DECISION' !== ($request['status'] ?? null)
            || 'imperator' !== ($request['recipient']['kind'] ?? null)
            || self::IMPERATOR_ID !== ($request['recipient']['id'] ?? null)
            || 'FOUNDRY_PERSONA_CONSTRUCTION_ONLY' !== ($request['requested_authority'] ?? null)
            || true === ($request['construction_authority'] ?? null) || true === ($request['persona_selection_authority'] ?? null)
            || true === ($request['spawning_authority'] ?? null) || true === ($request['seat_binding_authority'] ?? null)
            || true === ($request['execution_authority'] ?? null)) {
            throw new \RuntimeException('C82_CONSTRUCTION_REQUEST_INVALID: exact pending non-authorizing request is required.');
        }

        $demandRefs = $request['demands'] ?? null;
        if (!is_array($demandRefs) || [] === $demandRefs) {
            throw new \RuntimeException('C83_CONSTRUCTION_DEMAND_SET_INVALID: exact Foundry demands are required.');
        }
        $seen = [];
        foreach ($demandRefs as $reference) {
            $demandId = is_array($reference) ? ($reference['demand_id'] ?? null) : null;
            if (!is_string($demandId) || isset($seen[$demandId])) {
                throw new \RuntimeException('C83_CONSTRUCTION_DEMAND_SET_INVALID: demand references must be exact and unique.');
            }
            $demand = $this->read($this->demandDirectory.'/'.$demandId.'.json', 'C83_CONSTRUCTION_DEMAND_SET_INVALID');
            if (!$this->digestMatches($demand) || 'imperium.foundry-persona-construction-demand/v1' !== ($demand['schema'] ?? null)
                || ($request['instance_id'] ?? null) !== ($demand['instance_id'] ?? null)
                || ($request['proceeding_id'] ?? null) !== ($demand['proceeding_id'] ?? null)
                || ($reference['record_digest'] ?? null) !== ($demand['record_digest'] ?? null)
                || ($reference['profession'] ?? null) !== ($demand['profession'] ?? null)
                || ($request['source_disposition_id'] ?? null) !== ($demand['source_disposition_id'] ?? null)
                || ($request['source_disposition_digest'] ?? null) !== ($demand['source_disposition_digest'] ?? null)
                || 'PENDING_CURIA_CONSTRUCTION_AUTHORIZATION' !== ($demand['status'] ?? null)
                || true === ($demand['construction_authority'] ?? null) || true === ($demand['persona_selection_authority'] ?? null)
                || true === ($demand['spawning_authority'] ?? null) || true === ($demand['seat_binding_authority'] ?? null)
                || true === ($demand['execution_authority'] ?? null)) {
                throw new \RuntimeException('C83_CONSTRUCTION_DEMAND_SET_INVALID: demand lineage or authority state is invalid.');
            }
            $seen[$demandId] = true;
        }

        $actId ??= 'construction-authorization-'.substr(hash('sha256', CanonicalJson::encode([
            $requestId, $request['record_digest'], $demandRefs, self::IMPERATOR_ID,
        ])), 0, 20);
        if (!preg_match('/^construction-authorization-[a-zA-Z0-9._-]+$/', $actId)) {
            throw new \InvalidArgumentException('C84_CONSTRUCTION_ACT_INVALID: act identity is invalid.');
        }
        return $this->persist($actId, [
            'schema' => 'imperium.imperator-construction-authorization/v1',
            'kind' => 'FOUNDRY_PERSONA_CONSTRUCTION_AUTHORIZATION',
            'act_id' => $actId,
            'instance_id' => $request['instance_id'],
            'proceeding_id' => $request['proceeding_id'],
            'actor' => ['kind' => 'imperator', 'id' => self::IMPERATOR_ID],
            'authority_basis' => 'development-local-cli',
            'source_request_id' => $requestId,
            'source_request_digest' => $request['record_digest'],
            'demands' => $demandRefs,
            'disposition' => 'AUTHORIZED_FOR_EXACT_DEMANDS',
            'authorized_authority' => 'FOUNDRY_PERSONA_CONSTRUCTION_ONLY',
            'construction_authority' => true,
            'persona_selection_authority' => false,
            'spawning_authority' => false,
            'seat_binding_authority' => false,
            'execution_authority' => false,
        ]);
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) throw new \RuntimeException($error);
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function digestMatches(array $record): bool
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);
        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }

    private function persist(string $actId, array $act): array
    {
        if (!is_dir($this->decisionDirectory) && !mkdir($this->decisionDirectory, 0770, true) && !is_dir($this->decisionDirectory)) {
            throw new \RuntimeException('Construction authorization decision directory cannot be created.');
        }
        $act['record_digest'] = hash('sha256', CanonicalJson::encode($act));
        $path = $this->decisionDirectory.'/'.$actId.'.json';
        if (is_file($path)) {
            $existing = $this->read($path, 'C85_CONSTRUCTION_ACT_ABSENT');
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($act)) throw new \RuntimeException('C86_CONSTRUCTION_ACT_REPLAY_CONFLICT');
            return $existing;
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($act, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('Construction authorization act cannot be committed atomically.');
        }
        return $act;
    }
}
