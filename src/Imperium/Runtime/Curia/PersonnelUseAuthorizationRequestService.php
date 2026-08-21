<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PersonnelUseAuthorizationRequestService
{
    private string $dispositionDirectory;
    private string $requestDirectory;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $projectDir, private ProceedingStore $proceedings)
    {
        $this->dispositionDirectory = $projectDir.'/var/imperium/offices/guildhall/personnel-use-dispositions';
        $this->requestDirectory = $projectDir.'/var/imperium/curia/personnel-use-authorization-requests';
    }

    public function request(string $dispositionId): array
    {
        if (!preg_match('/^personnel-use-disposition-[a-f0-9]{20}$/', $dispositionId)) {
            throw new \InvalidArgumentException('C122_PERSONNEL_USE_DISPOSITION_ID_INVALID');
        }
        $disposition = $this->read($this->dispositionDirectory.'/'.$dispositionId.'.json', 'C123_PERSONNEL_USE_DISPOSITION_ABSENT');
        $slots = $disposition['resolved_capability_slots'] ?? null;
        if (!$this->digestMatches($disposition)
            || 'imperium.guildhall-personnel-use-disposition/v1' !== ($disposition['schema'] ?? null)
            || $dispositionId !== ($disposition['disposition_id'] ?? null)
            || 'CAPABILITY_SLOTS_RESOLVED_PENDING_USE_AUTHORIZATION' !== ($disposition['disposition'] ?? null)
            || 'CAPABILITY_TO_PROFESSION' !== ($disposition['translation_boundary']['name'] ?? null)
            || 'guildhall.guildmaster' !== ($disposition['translation_boundary']['authority'] ?? null)
            || true !== ($disposition['final_personnel_disposition'] ?? null)
            || true !== ($disposition['personnel_use_request_authority'] ?? null)
            || true === ($disposition['reservation_authority'] ?? null)
            || true === ($disposition['profile_derivation_authority'] ?? null)
            || true === ($disposition['spawning_authority'] ?? null)
            || true === ($disposition['seat_binding_authority'] ?? null)
            || true === ($disposition['execution_authority'] ?? null)
            || true !== ($disposition['sealed'] ?? null)
            || !is_array($slots) || [] === $slots
        ) {
            throw new \RuntimeException('C124_PERSONNEL_USE_DISPOSITION_INVALID');
        }
        $proceedingId = $disposition['proceeding_id'] ?? null;
        $proceeding = is_string($proceedingId) ? $this->proceedings->find($proceedingId) : null;
        if (!is_array($proceeding) || ($proceeding['instance_id'] ?? null) !== ($disposition['instance_id'] ?? null)) {
            throw new \RuntimeException('C125_PERSONNEL_USE_PROCEEDING_INVALID');
        }

        $commitments = [];
        $seen = [];
        foreach ($slots as $slot) {
            $slotId = is_array($slot) ? ($slot['capability_slot_id'] ?? null) : null;
            $requirements = is_array($slot) ? ($slot['capability_requirements'] ?? null) : null;
            $profession = is_array($slot) ? ($slot['profession'] ?? null) : null;
            $persona = is_array($slot) ? ($slot['persona'] ?? null) : null;
            $resolutionDigest = is_array($slot) ? ($slot['guildhall_resolution_digest'] ?? null) : null;
            if (!is_string($slotId) || '' === trim($slotId) || isset($seen[$slotId])
                || !is_array($requirements) || [] === $requirements
                || !is_string($profession) || '' === trim($profession)
                || !is_array($persona)
                || !is_string($persona['custody_id'] ?? null) || '' === trim($persona['custody_id'])
                || !is_string($persona['persona_id'] ?? null) || '' === trim($persona['persona_id'])
                || !is_string($resolutionDigest) || !preg_match('/^[a-f0-9]{64}$/', $resolutionDigest)
            ) {
                throw new \RuntimeException('C126_CAPABILITY_COMMITMENT_INVALID');
            }
            foreach ($requirements as $requirement) {
                if (!is_string($requirement) || '' === trim($requirement)) {
                    throw new \RuntimeException('C126_CAPABILITY_COMMITMENT_INVALID');
                }
            }
            $seen[$slotId] = true;
            $commitments[] = [
                'capability_slot_id' => $slotId,
                'capability_requirements' => $requirements,
                'profession' => $profession,
                'persona' => $persona,
                'guildhall_resolution_digest' => $resolutionDigest,
            ];
        }

        $requestId = 'personnel-use-authorization-request-'.substr(hash('sha256', CanonicalJson::encode([
            $dispositionId, $disposition['record_digest'], $commitments,
        ])), 0, 20);
        return $this->persist($requestId, [
            'schema' => 'imperium.curia-personnel-use-authorization-request/v1',
            'request_id' => $requestId,
            'instance_id' => $disposition['instance_id'],
            'proceeding_id' => $proceedingId,
            'requester' => ['office' => 'curia', 'seat' => 'curia.seneschal'],
            'recipient' => ['kind' => 'imperator', 'id' => 'imperator-development-root'],
            'source_language' => 'FUNCTIONAL_CAPABILITIES_WITH_GUILDHALL_PERSONNEL_RESOLUTION',
            'guildhall_disposition' => ['id' => $dispositionId, 'digest' => $disposition['record_digest']],
            'personnel_commitments' => $commitments,
            'personnel_resolution_boundary' => [
                'resolution_authority' => 'guildhall.guildmaster',
                'curia_role' => 'PRESENTATION_ONLY',
                'curia_profession_selection_authority' => false,
                'curia_persona_selection_authority' => false,
                'curia_substitution_authority' => false,
            ],
            'question' => 'Authorize use of these exact Guildhall-resolved professions and Personas for the correlated mission capability slots?',
            'requested_authority' => 'PERSONNEL_USE_COMMITMENT_ONLY',
            'allowed_dispositions' => ['AUTHORIZED', 'REFUSED', 'RETURNED_FOR_REVISION', 'ALTERNATIVE_PROPOSED', 'CLARIFICATION_REQUIRED', 'DEFERRED'],
            'status' => 'PENDING_IMPERATOR_DECISION',
            'personnel_use_authority' => false,
            'reservation_authority' => false,
            'profile_derivation_authority' => false,
            'spawning_authority' => false,
            'seat_binding_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ]);
    }

    private function persist(string $id, array $record): array
    {
        if (!is_dir($this->requestDirectory) && !mkdir($this->requestDirectory, 0770, true) && !is_dir($this->requestDirectory)) {
            throw new \RuntimeException('C127_PERSONNEL_USE_REQUEST_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->requestDirectory.'/'.$id.'.json';
        if (is_file($path)) {
            $existing = $this->read($path, 'C128_PERSONNEL_USE_REQUEST_CONFLICT');
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) throw new \RuntimeException('C128_PERSONNEL_USE_REQUEST_CONFLICT');
            return $existing;
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('C127_PERSONNEL_USE_REQUEST_FAILED');
        }
        return $record;
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
}
