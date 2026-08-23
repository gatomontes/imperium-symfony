<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Guildhall;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PersonnelUseAuthorizationAcceptanceService
{
    private string $decisionDirectory;
    private string $requestDirectory;
    private string $dispositionDirectory;
    private string $acceptanceDirectory;
    private string $garrisonInbox;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $projectDir)
    {
        $this->decisionDirectory = $projectDir.'/var/imperium/curia/personnel-use-authorization-decisions';
        $this->requestDirectory = $projectDir.'/var/imperium/curia/personnel-use-authorization-requests';
        $this->dispositionDirectory = $projectDir.'/var/imperium/offices/guildhall/personnel-use-dispositions';
        $this->acceptanceDirectory = $projectDir.'/var/imperium/offices/guildhall/personnel-use-authorization-acceptances';
        $this->garrisonInbox = $projectDir.'/var/imperium/offices/garrison/persona-reservation-inbox';
    }

    public function accept(string $actId): array
    {
        if (!preg_match('/^personnel-use-decision-[a-f0-9]{20}$/', $actId)) {
            throw new \InvalidArgumentException('G81_PERSONNEL_USE_DECISION_ID_INVALID');
        }
        $act = $this->read($this->decisionDirectory.'/'.$actId.'.json', 'G82_PERSONNEL_USE_DECISION_ABSENT');
        $requestId = $act['source_request_id'] ?? null;
        $request = is_string($requestId) ? $this->read($this->requestDirectory.'/'.$requestId.'.json', 'G83_PERSONNEL_USE_CHAIN_INVALID') : [];
        $dispositionId = $request['guildhall_disposition']['id'] ?? null;
        $disposition = is_string($dispositionId) ? $this->read($this->dispositionDirectory.'/'.$dispositionId.'.json', 'G83_PERSONNEL_USE_CHAIN_INVALID') : [];

        if (!$this->digestMatches($act) || !$this->digestMatches($request) || !$this->digestMatches($disposition)
            || 'imperium.imperator-personnel-use-decision/v1' !== ($act['schema'] ?? null)
            || 'AUTHORIZED' !== ($act['disposition'] ?? null)
            || 'AUTHORIZED_PENDING_GUILDHALL_ACCEPTANCE' !== ($act['status'] ?? null)
            || true !== ($act['personnel_use_authority'] ?? null)
            || true !== ($act['personnel_use_authority_exercisable'] ?? null)
            || true === ($act['reservation_authority'] ?? null)
            || 'imperium.curia-personnel-use-authorization-request/v1' !== ($request['schema'] ?? null)
            || 'PENDING_IMPERATOR_DECISION' !== ($request['status'] ?? null)
            || ($act['source_request_digest'] ?? null) !== ($request['record_digest'] ?? null)
            || 'imperium.guildhall-personnel-use-disposition/v1' !== ($disposition['schema'] ?? null)
            || $dispositionId !== ($disposition['disposition_id'] ?? null)
            || 'CAPABILITY_SLOTS_RESOLVED_PENDING_USE_AUTHORIZATION' !== ($disposition['disposition'] ?? null)
            || ($request['guildhall_disposition']['digest'] ?? null) !== ($disposition['record_digest'] ?? null)
            || ($act['guildhall_disposition'] ?? null) !== ($request['guildhall_disposition'] ?? null)
            || ($act['personnel_commitments'] ?? null) !== ($request['personnel_commitments'] ?? null)
            || ($request['personnel_resolution_boundary']['resolution_authority'] ?? null) !== 'guildhall.guildmaster'
            || ($request['personnel_resolution_boundary']['curia_role'] ?? null) !== 'PRESENTATION_ONLY'
            || true !== ($disposition['final_personnel_disposition'] ?? null)
            || true !== ($disposition['personnel_use_request_authority'] ?? null)
            || true === ($disposition['reservation_authority'] ?? null)
            || true !== ($disposition['sealed'] ?? null)
            || ($act['instance_id'] ?? null) !== ($request['instance_id'] ?? null)
            || ($act['proceeding_id'] ?? null) !== ($request['proceeding_id'] ?? null)
            || ($act['instance_id'] ?? null) !== ($disposition['instance_id'] ?? null)
            || ($act['proceeding_id'] ?? null) !== ($disposition['proceeding_id'] ?? null)
        ) {
            throw new \RuntimeException('G83_PERSONNEL_USE_CHAIN_INVALID');
        }

        $commitments = $request['personnel_commitments'] ?? null;
        $resolvedSlots = $disposition['resolved_capability_slots'] ?? null;
        if (!is_array($commitments) || [] === $commitments || $commitments !== $resolvedSlots) {
            throw new \RuntimeException('G84_PERSONNEL_RESOLUTION_MISMATCH');
        }

        $acceptanceId = 'guildhall-personnel-use-acceptance-'.substr(hash('sha256', CanonicalJson::encode([$actId, $act['record_digest'], $dispositionId, $disposition['record_digest']])), 0, 20);
        $acceptance = $this->persist($this->acceptanceDirectory, $acceptanceId, [
            'schema' => 'imperium.guildhall-personnel-use-authorization-acceptance/v1',
            'acceptance_id' => $acceptanceId,
            'instance_id' => $act['instance_id'],
            'proceeding_id' => $act['proceeding_id'],
            'imperator_act_id' => $actId,
            'imperator_act_digest' => $act['record_digest'],
            'guildhall_disposition_id' => $dispositionId,
            'guildhall_disposition_digest' => $disposition['record_digest'],
            'personnel_commitments' => $commitments,
            'accepted_by' => 'guildhall.guildmaster',
            'authorization_accepted' => true,
            'status' => 'AUTHORIZED_PERSONNEL_ACCEPTED_PENDING_GARRISON_RESERVATION',
            'garrison_reservation_request_authority' => true,
            'reservation_authority' => false,
            'profile_derivation_authority' => false,
            'spawning_authority' => false,
            'seat_binding_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ], 'G85_PERSONNEL_USE_ACCEPTANCE_CONFLICT');

        $requests = [];
        foreach ($commitments as $commitment) {
            $requestId = 'persona-reservation-request-'.substr(hash('sha256', CanonicalJson::encode([$acceptanceId, $acceptance['record_digest'], $commitment])), 0, 20);
            $requests[] = $this->persist($this->garrisonInbox, $requestId, [
                'schema' => 'imperium.guildhall-garrison-persona-reservation-request/v1',
                'request_id' => $requestId,
                'instance_id' => $act['instance_id'],
                'proceeding_id' => $act['proceeding_id'],
                'issuer' => 'guildhall.guildmaster',
                'recipient' => 'garrison.constable',
                'source_acceptance_id' => $acceptanceId,
                'source_acceptance_digest' => $acceptance['record_digest'],
                'imperator_act_id' => $actId,
                'imperator_act_digest' => $act['record_digest'],
                'guildhall_disposition_id' => $dispositionId,
                'guildhall_disposition_digest' => $disposition['record_digest'],
                'personnel_commitment' => $commitment,
                'status' => 'PENDING_CONSTABLE_RESERVATION_DISPOSITION',
                'reservation_requested' => true,
                'reservation_authority' => false,
                'profile_derivation_authority' => false,
                'spawning_authority' => false,
                'seat_binding_authority' => false,
                'execution_authority' => false,
                'sealed' => true,
            ], 'G86_PERSONA_RESERVATION_REQUEST_CONFLICT');
        }

        return ['acceptance' => $acceptance, 'reservation_requests' => $requests];
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

    private function persist(string $directory, string $id, array $record, string $conflict): array
    {
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) throw new \RuntimeException('G87_PERSONNEL_USE_ACCEPTANCE_FAILED');
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $directory.'/'.$id.'.json';
        if (is_file($path)) {
            $existing = $this->read($path, $conflict);
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) throw new \RuntimeException($conflict);
            return $existing;
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('G87_PERSONNEL_USE_ACCEPTANCE_FAILED');
        }
        return $record;
    }
}
