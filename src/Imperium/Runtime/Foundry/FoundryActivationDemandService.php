<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;

final readonly class FoundryActivationDemandService
{
    private string $authorizationInbox;
    private string $demandDirectory;

    public function __construct(string $projectDir)
    {
        $this->authorizationInbox = $projectDir.'/var/imperium/offices/foundry/inbox/construction-authorizations';
        $this->demandDirectory = $projectDir.'/var/imperium/mastermason/spawning-requests';
    }

    public function demand(string $deliveryId): array
    {
        if (!preg_match('/^construction-authorization-delivery-[a-f0-9]{20}$/', $deliveryId)) {
            throw new \InvalidArgumentException('F10_ACTIVATION_DELIVERY_INVALID: exact Foundry authorization delivery is required.');
        }
        $delivery = $this->read($this->authorizationInbox.'/'.$deliveryId.'.json', 'F11_ACTIVATION_DELIVERY_ABSENT');
        $act = $delivery['authorization_act'] ?? null;
        if (!is_array($act) || !$this->digestMatches($delivery) || !$this->digestMatches($act)
            || $deliveryId !== ($delivery['delivery_id'] ?? null)
            || 'imperium.foundry-construction-authorization-delivery/v1' !== ($delivery['schema'] ?? null)
            || 'foundry' !== ($delivery['office'] ?? null) || 'foundry' !== ($delivery['target'] ?? null)
            || 'DELIVERED_PENDING_FOUNDRY_ACCEPTANCE' !== ($delivery['status'] ?? null)
            || null !== ($delivery['recipient_acceptance'] ?? null)
            || true !== ($delivery['construction_authority'] ?? null)
            || true === ($delivery['persona_selection_authority'] ?? null) || true === ($delivery['spawning_authority'] ?? null)
            || true === ($delivery['seat_binding_authority'] ?? null) || true === ($delivery['execution_authority'] ?? null)
            || ($delivery['authorization_act_id'] ?? null) !== ($act['act_id'] ?? null)
            || ($delivery['authorization_act_digest'] ?? null) !== ($act['record_digest'] ?? null)) {
            throw new \RuntimeException('F12_ACTIVATION_DELIVERY_INVALID: exact unaccepted bounded Foundry delivery is required.');
        }

        $requiredSeats = [[
            'seat' => 'foundry.artificer',
            'profile' => 'offices/foundry/profile-artificer.md',
            'activation_policy' => 'resident',
            'status' => 'BLOCKED_PENDING_CANONICAL_STAFF_ARTIFACTS',
        ]];
        $demandId = 'foundry-activation-'.substr(hash('sha256', CanonicalJson::encode([
            $deliveryId, $delivery['record_digest'], $requiredSeats,
        ])), 0, 20);
        return $this->persist($demandId, [
            'schema' => 'imperium.office-activation-demand/v1',
            'demand_id' => $demandId,
            'requester' => 'foundry.inbox-router',
            'recipient' => 'mastermason',
            'office' => 'foundry',
            'authorization_delivery_id' => $deliveryId,
            'authorization_delivery_digest' => $delivery['record_digest'],
            'authorization_act_id' => $act['act_id'],
            'authorized_demands' => $delivery['authorized_demands'],
            'required_seats' => $requiredSeats,
            'missing_prerequisites' => [
                'canonical Artificer Persona artifact',
                'versioned current/active Artificer Profile definition and lifecycle attestations',
                'generic Officer substrate qualification by Conscription',
                'atomic Artificer Seat binding',
            ],
            'status' => 'CANONICAL_STAFF_ARTIFACTS_REQUIRED',
            'mission_persona_selection_required' => false,
            'construction_authority' => true,
            'spawning_authority' => false,
            'seat_binding_authority' => false,
            'recipient_acceptance' => false,
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

    private function persist(string $demandId, array $demand): array
    {
        if (!is_dir($this->demandDirectory) && !mkdir($this->demandDirectory, 0770, true) && !is_dir($this->demandDirectory)) {
            throw new \RuntimeException('MasterMason spawning-request directory cannot be created.');
        }
        $demand['record_digest'] = hash('sha256', CanonicalJson::encode($demand));
        $path = $this->demandDirectory.'/'.$demandId.'.json';
        if (is_file($path)) {
            $existing = $this->read($path, 'F13_ACTIVATION_DEMAND_ABSENT');
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($demand)) throw new \RuntimeException('F14_ACTIVATION_REPLAY_CONFLICT');
            return $existing;
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($demand, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('Foundry activation demand cannot be committed atomically.');
        }
        return $demand;
    }
}
