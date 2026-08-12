<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;

final readonly class ConstructionAuthorizationDeliveryService
{
    private string $decisionDirectory;
    private string $demandDirectory;
    private string $foundryAuthorizationInbox;

    public function __construct(string $projectDir)
    {
        $this->decisionDirectory = $projectDir.'/var/imperium/curia/authorization-decisions';
        $this->demandDirectory = $projectDir.'/var/imperium/offices/foundry/inbox';
        $this->foundryAuthorizationInbox = $projectDir.'/var/imperium/offices/foundry/inbox/construction-authorizations';
    }

    public function deliver(string $actId): array
    {
        if (!preg_match('/^construction-authorization-[a-zA-Z0-9._-]+$/', $actId)) {
            throw new \InvalidArgumentException('C90_CONSTRUCTION_ACT_INVALID: exact authorization act identity is required.');
        }
        $act = $this->read($this->decisionDirectory.'/'.$actId.'.json', 'C91_CONSTRUCTION_ACT_ABSENT');
        if (!$this->digestMatches($act) || $actId !== ($act['act_id'] ?? null)
            || 'imperium.imperator-construction-authorization/v1' !== ($act['schema'] ?? null)
            || 'FOUNDRY_PERSONA_CONSTRUCTION_AUTHORIZATION' !== ($act['kind'] ?? null)
            || 'AUTHORIZED_FOR_EXACT_DEMANDS' !== ($act['disposition'] ?? null)
            || 'FOUNDRY_PERSONA_CONSTRUCTION_ONLY' !== ($act['authorized_authority'] ?? null)
            || true !== ($act['construction_authority'] ?? null) || true === ($act['persona_selection_authority'] ?? null)
            || true === ($act['spawning_authority'] ?? null) || true === ($act['seat_binding_authority'] ?? null)
            || true === ($act['execution_authority'] ?? null)) {
            throw new \RuntimeException('C92_CONSTRUCTION_ACT_INVALID: exact bounded Imperator authorization is required.');
        }

        $demands = $act['demands'] ?? null;
        if (!is_array($demands) || [] === $demands) {
            throw new \RuntimeException('C93_CONSTRUCTION_DELIVERY_DEMAND_SET_INVALID: exact demand set is required.');
        }
        $seen = [];
        foreach ($demands as $reference) {
            $demandId = is_array($reference) ? ($reference['demand_id'] ?? null) : null;
            if (!is_string($demandId) || isset($seen[$demandId])) {
                throw new \RuntimeException('C93_CONSTRUCTION_DELIVERY_DEMAND_SET_INVALID: demand references must be exact and unique.');
            }
            $demand = $this->read($this->demandDirectory.'/'.$demandId.'.json', 'C93_CONSTRUCTION_DELIVERY_DEMAND_SET_INVALID');
            if (!$this->digestMatches($demand) || 'imperium.foundry-persona-construction-demand/v1' !== ($demand['schema'] ?? null)
                || ($act['instance_id'] ?? null) !== ($demand['instance_id'] ?? null)
                || ($act['proceeding_id'] ?? null) !== ($demand['proceeding_id'] ?? null)
                || ($reference['record_digest'] ?? null) !== ($demand['record_digest'] ?? null)
                || ($reference['profession'] ?? null) !== ($demand['profession'] ?? null)
                || 'PENDING_CURIA_CONSTRUCTION_AUTHORIZATION' !== ($demand['status'] ?? null)
                || true === ($demand['construction_authority'] ?? null) || true === ($demand['persona_selection_authority'] ?? null)
                || true === ($demand['spawning_authority'] ?? null) || true === ($demand['seat_binding_authority'] ?? null)
                || true === ($demand['execution_authority'] ?? null)) {
                throw new \RuntimeException('C93_CONSTRUCTION_DELIVERY_DEMAND_SET_INVALID: demand lineage or authority state is invalid.');
            }
            $seen[$demandId] = true;
        }

        $deliveryId = 'construction-authorization-delivery-'.substr(hash('sha256', CanonicalJson::encode([
            $actId, $act['record_digest'], $demands, 'foundry',
        ])), 0, 20);
        return $this->persist($deliveryId, [
            'schema' => 'imperium.foundry-construction-authorization-delivery/v1',
            'delivery_id' => $deliveryId,
            'office' => 'foundry',
            'target' => 'foundry',
            'instance_id' => $act['instance_id'],
            'proceeding_id' => $act['proceeding_id'],
            'authorization_act_id' => $actId,
            'authorization_act_digest' => $act['record_digest'],
            'authorized_demands' => $demands,
            'status' => 'DELIVERED_PENDING_FOUNDRY_ACCEPTANCE',
            'recipient_acceptance' => null,
            'construction_authority' => true,
            'persona_selection_authority' => false,
            'spawning_authority' => false,
            'seat_binding_authority' => false,
            'execution_authority' => false,
            'authorization_act' => $act,
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

    private function persist(string $deliveryId, array $delivery): array
    {
        if (!is_dir($this->foundryAuthorizationInbox) && !mkdir($this->foundryAuthorizationInbox, 0770, true) && !is_dir($this->foundryAuthorizationInbox)) {
            throw new \RuntimeException('Foundry authorization inbox cannot be created.');
        }
        $delivery['record_digest'] = hash('sha256', CanonicalJson::encode($delivery));
        $path = $this->foundryAuthorizationInbox.'/'.$deliveryId.'.json';
        if (is_file($path)) {
            $existing = $this->read($path, 'C94_CONSTRUCTION_DELIVERY_ABSENT');
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($delivery)) throw new \RuntimeException('C95_CONSTRUCTION_DELIVERY_REPLAY_CONFLICT');
            return $existing;
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($delivery, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('Construction authorization delivery cannot be committed atomically.');
        }
        return $delivery;
    }
}
