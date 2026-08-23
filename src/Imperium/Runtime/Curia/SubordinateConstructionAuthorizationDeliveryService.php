<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinateConstructionAuthorizationDeliveryService
{
    private string $decisionDirectory;
    private string $requestDirectory;
    private string $officeRoot;
    private string $guildhallInbox;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $projectDir)
    {
        $this->decisionDirectory = $projectDir.'/var/imperium/curia/subordinate-construction-decisions';
        $this->requestDirectory = $projectDir.'/var/imperium/curia/subordinate-construction-requests';
        $this->officeRoot = $projectDir.'/var/imperium/offices';
        $this->guildhallInbox = $projectDir.'/var/imperium/offices/guildhall/inbox/subordinate-personnel-authorizations';
    }

    public function deliver(string $actId): array
    {
        if (!preg_match('/^subordinate-construction-authorization-[a-zA-Z0-9._-]+$/', $actId)) throw new \InvalidArgumentException('C105_SUBORDINATE_ACT_ID_INVALID');
        $act = $this->read($this->decisionDirectory.'/'.$actId.'.json', 'C106_SUBORDINATE_ACT_ABSENT');
        $references = $act['resolutions'] ?? null;
        if (!$this->digestMatches($act) || $actId !== ($act['act_id'] ?? null)
            || 'imperium.imperator-subordinate-construction-authorization/v1' !== ($act['schema'] ?? null)
            || 'EXACT_SUBORDINATE_PERSONA_CONSTRUCTION_AUTHORIZATION' !== ($act['kind'] ?? null)
            || 'AUTHORIZED_FOR_EXACT_RESOLUTIONS' !== ($act['disposition'] ?? null)
            || 'EXACT_SUBORDINATE_PERSONA_CONSTRUCTION_ONLY' !== ($act['authorized_authority'] ?? null)
            || !is_array($references) || [] === $references || true !== ($act['construction_authority'] ?? null)
            || true === ($act['persona_selection_authority'] ?? null) || true === ($act['profile_approval_authority'] ?? null)
            || true === ($act['spawning_authority'] ?? null) || true === ($act['seat_binding_authority'] ?? null)
            || true === ($act['execution_authority'] ?? null)) throw new \RuntimeException('C107_SUBORDINATE_ACT_INVALID');

        $requestId = $act['source_request_id'] ?? null;
        $request = is_string($requestId) ? $this->read($this->requestDirectory.'/'.$requestId.'.json', 'C108_SUBORDINATE_REQUEST_CHANGED') : [];
        if (!$this->digestMatches($request) || ($act['source_request_digest'] ?? null) !== ($request['record_digest'] ?? null)
            || CanonicalJson::encode($references) !== CanonicalJson::encode($request['resolutions'] ?? null)
            || ($act['instance_id'] ?? null) !== ($request['instance_id'] ?? null)) throw new \RuntimeException('C108_SUBORDINATE_REQUEST_CHANGED');

        $seen = [];
        foreach ($references as $reference) {
            $resolutionId = is_array($reference) ? ($reference['resolution_id'] ?? null) : null; $office = is_array($reference) ? ($reference['office'] ?? null) : null;
            if (!is_string($resolutionId) || !in_array($office, ['hagiography', 'studium'], true) || isset($seen[$resolutionId])) throw new \RuntimeException('C109_SUBORDINATE_RESOLUTION_SET_CHANGED');
            $resolution = $this->read($this->officeRoot.'/'.$office.'/subordinate-resolutions/'.$resolutionId.'.json', 'C109_SUBORDINATE_RESOLUTION_SET_CHANGED');
            if (!$this->digestMatches($resolution) || ($reference['record_digest'] ?? null) !== ($resolution['record_digest'] ?? null)
                || ($act['instance_id'] ?? null) !== ($resolution['instance_id'] ?? null)
                || CanonicalJson::encode($reference['required_specializations'] ?? null) !== CanonicalJson::encode($resolution['decision']['required_specializations'] ?? null)
                || 'PENDING_CURIA_SUBORDINATE_CONSTRUCTION_AUTHORIZATION' !== ($resolution['status'] ?? null)
                || true !== ($resolution['sealed'] ?? null) || true === ($resolution['construction_authority'] ?? null)
                || true === ($resolution['persona_selection_authority'] ?? null) || true === ($resolution['profile_approval_authority'] ?? null)
                || true === ($resolution['spawning_authority'] ?? null) || true === ($resolution['seat_binding_authority'] ?? null)
                || true === ($resolution['execution_authority'] ?? null)) throw new \RuntimeException('C109_SUBORDINATE_RESOLUTION_SET_CHANGED');
            $seen[$resolutionId] = true;
        }

        $deliveryId = 'guildhall-subordinate-personnel-authorization-'.substr(hash('sha256', CanonicalJson::encode([$actId, $act['record_digest'], $references, 'guildhall'])), 0, 20);
        return $this->persist($deliveryId, [
            'schema' => 'imperium.guildhall-subordinate-personnel-authorization-delivery/v1',
            'delivery_id' => $deliveryId,
            'office' => 'guildhall',
            'target' => 'guildhall',
            'instance_id' => $act['instance_id'],
            'authorization_act_id' => $actId,
            'authorization_act_digest' => $act['record_digest'],
            'authorized_resolutions' => $references,
            'status' => 'DELIVERED_PENDING_GUILDHALL_COMMISSION',
            'recipient_acceptance' => null,
            'personnel_commission_authority' => true,
            'construction_authority' => false,
            'construction_authority_exercisable' => false,
            'persona_selection_authority' => false,
            'profile_approval_authority' => false,
            'spawning_authority' => false,
            'seat_binding_authority' => false,
            'execution_authority' => false,
            'authorization_act' => $act,
        ]);
    }

    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function digestMatches(array $record): bool { $digest = $record['record_digest'] ?? null; unset($record['record_digest']); return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record))); }
    private function persist(string $deliveryId, array $delivery): array
    {
        if (!is_dir($this->guildhallInbox) && !mkdir($this->guildhallInbox, 0770, true) && !is_dir($this->guildhallInbox)) throw new \RuntimeException('Guildhall subordinate-personnel authorization inbox cannot be created.');
        $delivery['record_digest'] = hash('sha256', CanonicalJson::encode($delivery)); $path = $this->guildhallInbox.'/'.$deliveryId.'.json';
        if (is_file($path)) { $existing = $this->read($path, 'C110_SUBORDINATE_DELIVERY_ABSENT'); if (CanonicalJson::encode($existing) !== CanonicalJson::encode($delivery)) throw new \RuntimeException('C111_SUBORDINATE_DELIVERY_REPLAY_CONFLICT'); return $existing; }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($delivery, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) { @unlink($temporary); throw new \RuntimeException('Subordinate construction authorization delivery cannot be committed atomically.'); }
        return $delivery;
    }
}
