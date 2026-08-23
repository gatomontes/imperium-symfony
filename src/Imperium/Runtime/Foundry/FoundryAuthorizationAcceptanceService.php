<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;

final readonly class FoundryAuthorizationAcceptanceService
{
    private string $inboxDirectory;
    private string $demandDirectory;
    private string $occupancyDirectory;
    private string $acceptanceDirectory;

    public function __construct(string $projectDir)
    {
        $this->inboxDirectory = $projectDir.'/var/imperium/offices/foundry/inbox/construction-authorizations';
        $this->demandDirectory = $projectDir.'/var/imperium/mastermason/spawning-requests';
        $this->occupancyDirectory = $projectDir.'/var/imperium/offices/foundry/occupancy';
        $this->acceptanceDirectory = $projectDir.'/var/imperium/offices/foundry/acceptances';
    }

    public function accept(string $deliveryId, string $bindingId): array
    {
        if (!preg_match('/^construction-authorization-delivery-[a-f0-9]{20}$/', $deliveryId)) throw new \InvalidArgumentException('F60_DELIVERY_INVALID: exact Foundry construction authorization delivery is required.');
        if (!preg_match('/^foundry-artificer-binding-[a-f0-9]{20}$/', $bindingId)) throw new \InvalidArgumentException('F61_BINDING_INVALID: exact Artificer binding identity is required.');
        $delivery = $this->read($this->inboxDirectory.'/'.$deliveryId.'.json', 'F62_DELIVERY_ABSENT');
        $act = $delivery['authorization_act'] ?? null;
        if (!is_array($act) || !$this->digestMatches($delivery) || !$this->digestMatches($act)
            || $deliveryId !== ($delivery['delivery_id'] ?? null) || 'imperium.foundry-construction-authorization-delivery/v1' !== ($delivery['schema'] ?? null)
            || 'foundry' !== ($delivery['office'] ?? null) || 'foundry' !== ($delivery['target'] ?? null)
            || 'DELIVERED_PENDING_FOUNDRY_ACCEPTANCE' !== ($delivery['status'] ?? null) || null !== ($delivery['recipient_acceptance'] ?? null)
            || true !== ($delivery['construction_authority'] ?? null) || true === ($delivery['persona_selection_authority'] ?? null)
            || true === ($delivery['spawning_authority'] ?? null) || true === ($delivery['seat_binding_authority'] ?? null)
            || true === ($delivery['execution_authority'] ?? null) || ($delivery['authorization_act_id'] ?? null) !== ($act['act_id'] ?? null)
            || ($delivery['authorization_act_digest'] ?? null) !== ($act['record_digest'] ?? null)) {
            throw new \RuntimeException('F63_DELIVERY_INVALID: exact unaccepted bounded Foundry authorization is required.');
        }
        $binding = $this->read($this->occupancyDirectory.'/'.$bindingId.'.json', 'F64_BINDING_ABSENT');
        if (!$this->digestMatches($binding) || 'imperium.foundry-artificer-occupancy/v1' !== ($binding['schema'] ?? null)
            || 'foundry' !== ($binding['office'] ?? null) || 'foundry.artificer' !== ($binding['seat'] ?? null)
            || 'ACTIVE' !== ($binding['status'] ?? null) || true !== ($binding['binding_atomic'] ?? null)
            || true !== ($binding['foundry_construction_authority'] ?? null) || true === ($binding['recipient_acceptance'] ?? null)
            || true === ($binding['execution_authority'] ?? null)) {
            throw new \RuntimeException('F65_BINDING_INVALID: exact active bounded Artificer occupancy is required.');
        }
        $demandId = $binding['source_activation_demand_id'] ?? null;
        if (!is_string($demandId)) throw new \RuntimeException('F66_ACCEPTANCE_CHAIN_INVALID: source activation demand is absent.');
        $demand = $this->read($this->demandDirectory.'/'.$demandId.'.json', 'F66_ACCEPTANCE_CHAIN_INVALID');
        if (!$this->digestMatches($demand) || $demandId !== ($demand['demand_id'] ?? null)
            || $deliveryId !== ($demand['authorization_delivery_id'] ?? null) || ($delivery['record_digest'] ?? null) !== ($demand['authorization_delivery_digest'] ?? null)
            || ($delivery['authorization_act_id'] ?? null) !== ($demand['authorization_act_id'] ?? null)
            || 'foundry' !== ($demand['office'] ?? null) || true !== ($demand['construction_authority'] ?? null)) {
            throw new \RuntimeException('F66_ACCEPTANCE_CHAIN_INVALID: authorization delivery, activation demand, and Artificer occupancy do not agree.');
        }
        return $this->persist([
            'schema' => 'imperium.foundry-authorization-acceptance/v1',
            'acceptance_id' => 'foundry-acceptance-'.substr(hash('sha256', CanonicalJson::encode([$deliveryId, $delivery['record_digest'], $bindingId, $binding['record_digest'], $demand['record_digest']])), 0, 20),
            'instance_id' => $binding['instance_id'], 'delivery_id' => $deliveryId, 'delivery_digest' => $delivery['record_digest'],
            'authorization_act_id' => $act['act_id'], 'authorization_act_digest' => $act['record_digest'],
            'activation_demand_id' => $demandId, 'activation_demand_digest' => $demand['record_digest'],
            'binding_id' => $bindingId, 'binding_digest' => $binding['record_digest'],
            'actor' => ['seat' => 'foundry.artificer', 'manifestation_id' => $binding['manifestation_id'], 'occupancy_generation' => $binding['occupancy_generation']],
            'disposition' => 'ACCEPTED_FOR_EXACT_CONSTRUCTION', 'authorized_demands' => $delivery['authorized_demands'] ?? [],
            'recipient_acceptance' => true, 'foundry_construction_authority' => true,
            'persona_selection_authority' => false, 'spawning_authority' => false, 'seat_binding_authority' => false, 'execution_authority' => false,
        ]);
    }

    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function digestMatches(array $record): bool { $digest = $record['record_digest'] ?? null; unset($record['record_digest']); return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record))); }
    private function persist(array $acceptance): array
    {
        if (!is_dir($this->acceptanceDirectory) && !mkdir($this->acceptanceDirectory, 0770, true) && !is_dir($this->acceptanceDirectory)) throw new \RuntimeException('Foundry acceptance directory cannot be created.');
        $acceptance['record_digest'] = hash('sha256', CanonicalJson::encode($acceptance)); $path = $this->acceptanceDirectory.'/'.$acceptance['acceptance_id'].'.json';
        if (is_file($path)) { $existing = $this->read($path, 'F67_ACCEPTANCE_REPLAY_CONFLICT'); if (CanonicalJson::encode($existing) !== CanonicalJson::encode($acceptance)) throw new \RuntimeException('F67_ACCEPTANCE_REPLAY_CONFLICT: acceptance identity is already bound differently.'); return $existing; }
        foreach (glob($this->acceptanceDirectory.'/foundry-acceptance-*.json') ?: [] as $existingPath) { $existing = $this->read($existingPath, 'F67_ACCEPTANCE_REPLAY_CONFLICT'); if ($acceptance['delivery_id'] === ($existing['delivery_id'] ?? null)) throw new \RuntimeException('F68_AUTHORIZATION_ALREADY_DISPOSED: delivery already has a Foundry acceptance disposition.'); }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($acceptance, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) { @unlink($temporary); throw new \RuntimeException('Foundry acceptance cannot be committed atomically.'); }
        return $acceptance;
    }
}
