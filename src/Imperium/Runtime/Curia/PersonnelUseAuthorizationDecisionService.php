<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PersonnelUseAuthorizationDecisionService
{
    private const string IMPERATOR_ID = 'imperator-development-root';
    private const array DISPOSITIONS = ['AUTHORIZED', 'REFUSED', 'RETURNED_FOR_REVISION', 'ALTERNATIVE_PROPOSED', 'CLARIFICATION_REQUIRED', 'DEFERRED'];

    private string $requestDirectory;
    private string $decisionDirectory;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $projectDir)
    {
        $this->requestDirectory = $projectDir.'/var/imperium/curia/personnel-use-authorization-requests';
        $this->decisionDirectory = $projectDir.'/var/imperium/curia/personnel-use-authorization-decisions';
    }

    public function decide(string $requestId, string $disposition, string $response, ?string $limitations = null): array
    {
        if (!preg_match('/^personnel-use-authorization-request-[a-f0-9]{20}$/', $requestId)) {
            throw new \InvalidArgumentException('C129_PERSONNEL_USE_REQUEST_ID_INVALID');
        }
        $disposition = strtoupper(trim($disposition));
        $response = trim($response);
        $limitations = null === $limitations ? null : trim($limitations);
        if (!in_array($disposition, self::DISPOSITIONS, true) || '' === $response || '' === $limitations) {
            throw new \InvalidArgumentException('C130_IMPERATOR_DISPOSITION_INVALID');
        }
        $request = $this->read($this->requestDirectory.'/'.$requestId.'.json', 'C131_PERSONNEL_USE_REQUEST_ABSENT');
        if (!$this->digestMatches($request)
            || 'imperium.curia-personnel-use-authorization-request/v1' !== ($request['schema'] ?? null)
            || $requestId !== ($request['request_id'] ?? null)
            || 'PENDING_IMPERATOR_DECISION' !== ($request['status'] ?? null)
            || self::IMPERATOR_ID !== ($request['recipient']['id'] ?? null)
            || 'FUNCTIONAL_CAPABILITIES_WITH_GUILDHALL_PERSONNEL_RESOLUTION' !== ($request['source_language'] ?? null)
            || 'PERSONNEL_USE_COMMITMENT_ONLY' !== ($request['requested_authority'] ?? null)
            || self::DISPOSITIONS !== ($request['allowed_dispositions'] ?? null)
            || true === ($request['personnel_use_authority'] ?? null)
            || true === ($request['reservation_authority'] ?? null)
            || true === ($request['profile_derivation_authority'] ?? null)
            || true === ($request['spawning_authority'] ?? null)
            || true === ($request['seat_binding_authority'] ?? null)
            || true === ($request['execution_authority'] ?? null)
            || true !== ($request['sealed'] ?? null)
            || 'guildhall.guildmaster' !== ($request['personnel_resolution_boundary']['resolution_authority'] ?? null)
            || 'PRESENTATION_ONLY' !== ($request['personnel_resolution_boundary']['curia_role'] ?? null)
            || false !== ($request['personnel_resolution_boundary']['curia_profession_selection_authority'] ?? null)
            || false !== ($request['personnel_resolution_boundary']['curia_persona_selection_authority'] ?? null)
            || false !== ($request['personnel_resolution_boundary']['curia_substitution_authority'] ?? null)
            || !is_array($request['personnel_commitments'] ?? null)
            || [] === $request['personnel_commitments']
        ) {
            throw new \RuntimeException('C132_PERSONNEL_USE_REQUEST_INVALID');
        }
        $seenSlots = [];
        foreach ($request['personnel_commitments'] as $commitment) {
            $slotId = is_array($commitment) ? ($commitment['capability_slot_id'] ?? null) : null;
            $requirements = is_array($commitment) ? ($commitment['capability_requirements'] ?? null) : null;
            $profession = is_array($commitment) ? ($commitment['profession'] ?? null) : null;
            $persona = is_array($commitment) ? ($commitment['persona'] ?? null) : null;
            $resolutionDigest = is_array($commitment) ? ($commitment['guildhall_resolution_digest'] ?? null) : null;
            if (!is_string($slotId) || '' === trim($slotId) || isset($seenSlots[$slotId])
                || !is_array($requirements) || [] === $requirements
                || !is_string($profession) || '' === trim($profession)
                || !is_array($persona)
                || !is_string($persona['custody_id'] ?? null) || '' === trim($persona['custody_id'])
                || !is_string($persona['persona_id'] ?? null) || '' === trim($persona['persona_id'])
                || !is_string($resolutionDigest) || !preg_match('/^[a-f0-9]{64}$/', $resolutionDigest)
            ) {
                throw new \RuntimeException('C132_PERSONNEL_USE_REQUEST_INVALID');
            }
            foreach ($requirements as $requirement) {
                if (!is_string($requirement) || '' === trim($requirement)) {
                    throw new \RuntimeException('C132_PERSONNEL_USE_REQUEST_INVALID');
                }
            }
            $seenSlots[$slotId] = true;
        }
        foreach (glob($this->decisionDirectory.'/personnel-use-decision-*.json') ?: [] as $path) {
            $prior = $this->read($path, 'C135_PERSONNEL_USE_DECISION_CONFLICT');
            if ($requestId === ($prior['source_request_id'] ?? null)) {
                if (($prior['disposition'] ?? null) === $disposition && ($prior['response'] ?? null) === $response && ($prior['limitations'] ?? null) === $limitations) return $prior;
                throw new \RuntimeException('C135_PERSONNEL_USE_DECISION_CONFLICT');
            }
        }

        $authorized = 'AUTHORIZED' === $disposition;
        $actId = 'personnel-use-decision-'.substr(hash('sha256', CanonicalJson::encode([
            $requestId, $request['record_digest'], $disposition, $response, $limitations, self::IMPERATOR_ID,
        ])), 0, 20);
        return $this->persist($actId, [
            'schema' => 'imperium.imperator-personnel-use-decision/v1',
            'kind' => 'PERSONNEL_USE_DECISION',
            'act_id' => $actId,
            'instance_id' => $request['instance_id'],
            'proceeding_id' => $request['proceeding_id'],
            'actor' => ['kind' => 'imperator', 'id' => self::IMPERATOR_ID],
            'authority_basis' => 'development-local-cli',
            'source_request_id' => $requestId,
            'source_request_digest' => $request['record_digest'],
            'guildhall_disposition' => $request['guildhall_disposition'],
            'personnel_commitments' => $request['personnel_commitments'],
            'personnel_resolution_boundary' => $request['personnel_resolution_boundary'],
            'disposition' => $disposition,
            'response' => $response,
            'limitations' => $limitations,
            'status' => $authorized ? 'AUTHORIZED_PENDING_GUILDHALL_ACCEPTANCE' : 'NON_AUTHORIZING_IMPERATOR_DISPOSITION_RECORDED',
            'personnel_use_authority' => $authorized,
            'personnel_use_authority_exercisable' => $authorized,
            'guildhall_followup_required' => in_array($disposition, ['RETURNED_FOR_REVISION', 'ALTERNATIVE_PROPOSED', 'CLARIFICATION_REQUIRED'], true),
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
        if (!is_dir($this->decisionDirectory) && !mkdir($this->decisionDirectory, 0770, true) && !is_dir($this->decisionDirectory)) throw new \RuntimeException('C133_PERSONNEL_USE_DECISION_FAILED');
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->decisionDirectory.'/'.$id.'.json';
        if (is_file($path)) {
            $existing = $this->read($path, 'C135_PERSONNEL_USE_DECISION_CONFLICT');
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) throw new \RuntimeException('C135_PERSONNEL_USE_DECISION_CONFLICT');
            return $existing;
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('C133_PERSONNEL_USE_DECISION_FAILED');
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
